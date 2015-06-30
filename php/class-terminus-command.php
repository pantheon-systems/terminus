<?php
use \Terminus\Endpoint;
use \Terminus\Request;
use \Terminus\Fixtures;
use \Terminus\Session;
use \Terminus\Auth;
use \Terminus\Loggers\Regular as Logger;

/**
 * Base class for Terminus commands
 *
 * @package terminus
 */
abstract class Terminus_Command {

  public $cache;
  public $session;
  public $sites;

  protected $_func;
  protected $_siteInfo;
  protected $_bindings;
  protected static $_blacklist = array('password');

  public function __construct() {
    # Load commonly used data from cache.
    $this->cache = Terminus::get_cache();
    $this->session = Session::instance();
    $this->sites = $this->cache->get_data('sites');
  }

  /**
   * Make a request to the Dashbord's internal API.
   *
   * @param $realm
   *    Permissions realm for data request: currently "user" or "site" but in the
   *    future this could also be "organization" or another high-level business
   *    object (e.g. "product" for managing your app). Can also be "public" to
   *    simply pull read-only data that is not privileged.
   *
   * @param $uuid
   *    The UUID of the item in the realm you want to access.
   *
   * @param $method
   *    HTTP method (verb) to use.
   *
   * @param $data
   *    A native PHP data structure (int, string, arary or simple object) to be
   *    sent along with the request. Will be encoded as JSON for you.
   */
  public static function request($realm, $uuid, $path = FALSE, $method = 'GET', $options = NULL) {
    if (!in_array($realm,array('login','user','public')) AND !Terminus::is_test()) {
      Auth::loggedIn();
    }

    try {
      $cache = Terminus::get_cache();

      // combine session realm uuid and path to get a unique key
      // @todo need cache "groups"
      $cachekey = md5( Session::getValue('user_uuid').$uuid.$realm.$path );
      $data = $cache->get_data($cachekey);

      // check the request cache
      if ("GET" == $method AND !Terminus::get_config('nocache') AND !getenv('CLI_TEST_MODE') AND !empty($data)) {
        if (Terminus::get_config('debug')) {
          Logger::debug('CacheKey: '.$cachekey);
        }
        return (array) $data;
      }

      // for some methods we'll assume the cache should be invalidated
      if ( in_array($method, array("POST","PUT","DELETE")) ) {
        $cache->flush(null, 'session');
      }

      if (!in_array($realm,array('login','user'))) {
        $options['cookies'] = array('X-Pantheon-Session' => Session::getValue('session'));
        $options['verify'] = false;
      }

      $url = Endpoint::get(array('realm'=>$realm, 'uuid'=>$uuid, 'path'=>$path));
      if (Terminus::get_config('debug')) {
        Logger::debug('Request URL: '.$url);
      }
      $resp = Request::send($url, $method, $options);
      $json = $resp->getBody(TRUE);

      $data = array(
        'info' => $resp->getInfo(),
        'headers' => $resp->getRawHeaders(),
        'json' => $json,
        'data' => json_decode($json)
      );
      $cache->put_data($cachekey, $data);
      return $data;
    } catch( Guzzle\Http\Exception\BadResponseException $e ) {
      $response = $e->getResponse();
      \Terminus::error("%s", $response->getBody(TRUE) );
    } catch( Guzzle\Http\Exception\HttpException $e ) {
      $request = $e->getRequest();
      //die(Terminus_Command::stripSensitiveData());
      $sanitized_request = Terminus_Command::stripSensitiveData((string)$request, Terminus_Command::$_blacklist);
      \Terminus::error("Request %s had failed: %s", array($sanitized_request, $e->getMessage()) );
    } catch( Exception $e ) {
      \Terminus::error("Unrecognised request failure: %s", $e->getMessage() );
    }

  }

  public static function download($url, $target) {
    try {
      $response = Request::download($url,$target);
      return $target;
    } catch(Exception $e) {
      Terminus::error($e->getMessage());
    }
  }

  protected function _constructTableForResponse($data,$headers = array()) {
    $table = new \cli\Table();
    if (is_object($data)) {
      $data = (array)$data;
    }

    if (\Terminus\Utils\result_is_multiobj($data)) {
      if (!empty($headers)) {
        $table->setHeaders($headers);
      } elseif (property_exists($this, "_headers") AND !empty($this->_headers[$this->_func])) {
        if (is_array($this->_headers[$this->_func])) {
          $table->setHeaders($this->_headers[$this->_func]);
        }
      } else {
        $table->setHeaders(\Terminus\Utils\result_get_response_fields($data));
      }

      foreach ($data as $row => $row_data) {
        $row = array();
        foreach( $row_data as $key => $value) {
          if( is_array($value) OR is_object($value) ) {
            $value = join(", ",(array) $value);
          }
          $row[] = $value;
        }
        $table->addRow($row);
      }
    } else {
      if (!empty($headers)) {
        $table->setHeaders($headers);
      } else {
        //$table->setHeaders( array_keys($data) );
      }
      foreach( $data as $key=>$value ) {
        if( is_array($value) OR is_object($value) ) {
          $value = implode(", ",(array) $value);
        }
        $table->addRow( array( $key, $value ) );
      }
    }

    $table->display();
  }

  /**
   * Waits and returns response from workflow.
   * @package Terminus
   * @version 2.0
   * @param $object_name string -- i.e. sites / users / organization
   * @param $object_id string -- coresponding id
   * @param $workflow_id string -- workflow to wait on
   *
   * @deprecated Use new WorkFlow() object instead
   * Example: $this->waitOnWorkflow( "sites", "68b99b50-8942-4c66-b7e3-22b67445f55d", "e4f7e832-5644-11e4-81d4-bc764e111d20");
   */
  protected function waitOnWorkflow( $object_name, $object_id, $workflow_id ) {
    print "Working .";
    Terminus::set_config('nocache',true);
    $workflow = self::request( $object_name, $object_id, "workflows/$workflow_id", 'GET' );
    $result = $workflow['data']->result;
    $desc = $workflow['data']->active_description;
    $type = $workflow['data']->type;
    $tries = 0;
    while(!isset($result) AND ($tries < 100)) {
      if ( 'failed' == $result OR 'aborted' == $result ) {
        if (isset($workflow['data']->final_task) and !empty($workflow['data']->final_task->messages)) {
          foreach($workflow['data']->final_task->messages as $data => $message) {
            sprintf('[%s] %s', $message->level, $message->message);
          }
        } else {
          Terminus::error(PHP_EOL."Couldn't complete jobs: '{$type}'".PHP_EOL);
        }
      }
      $workflow = self::request( $object_name, $object_id, "workflows/{$workflow_id}", 'GET' );
      $result = $workflow['data']->result;
      if (Terminus::get_config('debug')) {
        print_r($workflow);
      }
      sleep(3);
      print ".";
      $tries++;
    }
    print PHP_EOL;
    if(("succeeded" === $workflow['data']->result) OR ("aborted" === $workflow['data']->result))
      return $workflow['data'];
    
    return false;
    unset($workflow);
  }

  protected function handleDisplay($data,$args = array(), $headers = null) {
    if (array_key_exists("json", $args) OR Terminus::get_config('json') )
      echo \Terminus\Utils\json_dump($data);
    else if (array_key_exists("bash", $args) OR Terminus::get_config('bash'))
      echo \Terminus\Utils\bash_out((array)$data);
    else
      $this->_constructTableForResponse((array)$data,$headers);
  }

  /**
   * Strips sensitive data out of the JSON printed in a request string
   * @package Terminus
   * @version 2.0
   * @param request string -- the string from which to strip data
   * @param blacklist array -- an array of strings which are the keys to remove from request
   */
  protected function stripSensitiveData($request, $blacklist = []) {
    //Locate the JSON in the string, turn to array
    $regex = '~\{(.*)\}~';
    preg_match($regex, $request, $matches);
    $request_array = json_decode($matches[0], true);

    //See if a blacklisted items are in the arrayed JSON, replace
    foreach($blacklist as $blacklisted_item) {
      if(isset($request_array[$blacklisted_item])) 
        $request_array[$blacklisted_item] = '*****';
    }

    //Turn array back to JSON, put back in string
    $result = str_replace($matches[0], json_encode($request_array), $request);
    return $result;
  }

}
