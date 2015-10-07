<?php

use Terminus\Auth;
use Terminus\Endpoint;
use Terminus\Request;
use Terminus\Session;
use Terminus\Utils;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Regular as Logger;

/**
 * The base class for Terminus commands
 */
abstract class TerminusCommand {
  public $cache;
  public $session;
  public $sites;

  protected static $blacklist = array('password');
  protected $func;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  /**
   * @var OutputterInterface
   */
  protected $outputter;

  /**
   * Instantiates object, sets cache and session
   *
   * @return [TerminusCommand] $this
   */
  public function __construct() {
    //Load commonly used data from cache
    $this->cache     = Terminus::get_cache();
    $this->logger    = Terminus::get_logger();
    $this->outputter = Terminus::get_outputter();
    $this->session   = Session::instance();
    if (!Terminus::is_test()) {
      $this->checkForUpdate();
    }
  }

  /**
   * Make a request to the Pantheon API
   *
   * @param [string] $realm   Permissions realm for data request (e.g. user,
   *   site organization, etc. Can also be "public" to simply pull read-only
   *   data that is not privileged.
   * @param [string] $uuid    The UUID of the item in the realm to access
   * @param [string] $path    API path (URL)
   * @param [string] $method  HTTP method to use
   * @param [mixed]  $options A native PHP data structure (e.g. int, string,
   *   array, or stdClass) to be sent along with the request
   * @return [array] $data
   */
  public static function request(
    $realm,
    $uuid,
    $path = false,
    $method = 'GET',
    $options = null
  ) {
    if (!in_array($realm, array('login', 'user', 'public'))) {
      Auth::loggedIn();
    }

    try {
      $cache = Terminus::get_cache();

      if (!in_array($realm, array('login', 'user'))) {
        $options['cookies'] = array(
          'X-Pantheon-Session' => Session::getValue('session')
        );
        $options['verify']  = false;
      }

      $url = Endpoint::get(
        array(
          'realm' => $realm,
          'uuid'  => $uuid,
          'path'  => $path,
        )
      );
      if (Terminus::get_config('debug')) {
        Terminus::log('debug', 'Request URL: ' . $url);
      }
      $resp = Request::send($url, $method, $options);
      $json = $resp->getBody(true);

      $data = array(
        'info' => $resp->getInfo(),
        'headers' => $resp->getRawHeaders(),
        'json' => $json,
        'data' => json_decode($json),
        'status_code' => $resp->getStatusCode()
      );
      return $data;
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      $response = $e->getResponse();
      throw new TerminusException($response->getBody(true));
    } catch (Guzzle\Http\Exception\HttpException $e) {
      $request = $e->getRequest();
      $sanitized_request = TerminusCommand::stripSensitiveData(
        (string)$request,
        TerminusCommand::$blacklist
      );
      throw new TerminusException('API Request Error. {msg} - Request: {req}', array('req' => $sanitized_request, 'msg' => $e->getMessage()));
    } catch (Exception $e) {
      throw new TerminusException('API Request Error: {msg}', array('msg' => $e->getMessage()));
    }

  }

  /**
   * Make a request to the Dashbord's internal API
   *
   * @param [string] $path    API path (URL)
   * @param [array]  $options Options for the request
   *   [string] method GET is default
   *   [mixed]  data   Native PHP data structure (e.g. int, string array, or
   *     simple object) to be sent along with the request. Will be encoded as
   *     JSON for you.
   * @return [array] $return
   */
  public static function paged_request($path, $options = array()) {
    $limit = 100;
    if (isset($options['limit'])) {
      $limit = $options['limit'];
    }

    //$results is an associative array so we don't refetch
    $results  = array();
    $finished = false;
    $start    = null;

    while (!$finished) {
      $paged_path = $path . '?limit=' . $limit;
      if ($start) {
        $paged_path .= '&start=' . $start;
      }

      $resp = self::simple_request($paged_path);

      $data = $resp['data'];
      if (count($data) > 0) {
        $start = end($data)->id;

        //If the last item of the results has previously been received,
        //that means there are no more pages to fetch
        if (isset($results[$start])) {
          $finished = true;
          continue;
        }

        foreach ($data as $item) {
          $results[$item->id] = $item;
        }
      } else {
        $finished = true;
      }
    }

    $return = array('data' => array_values($results));
    return $return;
  }

  /**
   * Simplified request method for Pantheon API
   *
   * @param [string] $path    API path (URL)
   * @param [array]  $options Options for the request
   *   [string] method GET is default
   *   [mixed]  data   Native PHP data structure (e.g. int, string array, or
   *     simple object) to be sent along with the request. Will be encoded as
   *     JSON for you.
   * @return [array] $data
   */
  public static function simple_request($path, $options = array()) {
    $req_options = array();

    $method = 'get';
    if (isset($options['method'])) {
      $method = $options['method'];
    }

    if (isset($options['data'])) {
      $req_options['body']    = json_encode($options['data']);
      $req_options['headers'] = array('Content-type' => 'application/json');
    }

    $url = 'https://' . TERMINUS_HOST . '/api/' . $path;

    if (Session::getValue('session')) {
      $req_options['cookies'] = array(
        'X-Pantheon-Session' => Session::getValue('session')
      );
      $req_options['verify']  = false;
    }

    try {
      $resp = Request::send($url, $method, $req_options);
    } catch (Guzzle\Http\Exception\BadResponseException $e) {
      throw new TerminusException('API Request Error: {msg}', array('msg' => $e->getMessage()));
    }

    $json = $resp->getBody(true);
    $data = array(
      'info' => $resp->getInfo(),
      'headers' => $resp->getRawHeaders(),
      'json' => $json,
      'data' => json_decode($json),
      'status_code' => $resp->getStatusCode()
    );
    return $data;
  }



  /**
   * Constructs table for data going to STDOUT
   * TODO: Complexity too high. Refactor.
   *
   * @param [mixed] $data    Object or hash of data for output
   * @param [array] $headers Array of strings for table headers
   * @return [void]
   */
  protected function constructTableForResponse($data, $headers = array()) {
    $table = new \cli\Table();
    if (is_object($data)) {
      $data = (array)$data;
    }

    if (Utils\result_is_multiobj($data)) {
      if (!empty($headers)) {
        $table->setHeaders($headers);
      } elseif (
        property_exists($this, '_headers')
        && !empty($this->_headers[$this->func])
      ) {
        if (is_array($this->_headers[$this->func])) {
          $table->setHeaders($this->_headers[$this->func]);
        }
      } else {
        $table->setHeaders(Utils\result_get_response_fields($data));
      }

      foreach ($data as $row => $row_data) {
        $row = array();
        foreach ($row_data as $key => $value) {
          if (is_array($value) || is_object($value)) {
            $value = join(', ', (array)$value);
          }
          $row[] = $value;
        }
        $table->addRow($row);
      }
    } else {
      if (!empty($headers)) {
        $table->setHeaders($headers);
      }
      foreach ($data as $key => $value) {
        if (is_array($value) || is_object($value)) {
          $value = implode(', ', (array)$value);
        }
        $table->addRow(array($key, $value));
      }
    }

    $table->display();
  }

  /**
   * Strips sensitive data out of the JSON printed in a request string
   *
   * @param [string] $request   The string with a JSON with sensitive data
   * @param [array]  $blacklist Array of string keys to remove from request
   * @return [string] $result Sensitive data-stripped version of $request
   */
  protected function stripSensitiveData($request, $blacklist = array()) {
    //Locate the JSON in the string, turn to array
    $regex = '~\{(.*)\}~';
    preg_match($regex, $request, $matches);
    $request_array = json_decode($matches[0], true);

    //See if a blacklisted items are in the arrayed JSON, replace
    foreach ($blacklist as $blacklisted_item) {
      if (isset($request_array[$blacklisted_item])) {
        $request_array[$blacklisted_item] = '*****';
      }
    }

    //Turn array back to JSON, put back in string
    $result = str_replace($matches[0], json_encode($request_array), $request);
    return $result;
  }

  /**
   * Outputs basic workflow success/failure messages
   *
   * @param [Workflow] $workflow Workflow to output message about
   * @return [void]
   */
  protected function workflowOutput($workflow) {
    if ($workflow->get('result') == 'succeeded') {
      $this->log()->info($workflow->get('active_description'));
    } else {
      $final_task = $workflow->get('final_task');
      $this->log()->error($final_task->reason);
    }
  }

  private function checkForUpdate() {
    $cache_data = $this->cache->get_data('latest_release', array('decode_array' => true));
    if (
      !$cache_data
      || ((int)$cache_data['check_date'] < (int)strtotime('-7 days'))
    ) {
      $current_version = $this->checkCurrentVersion();
    } else {
      $current_version = $cache_data['version'];
    }
    if (version_compare($cache_data['version'], TERMINUS_VERSION, '>')) {
      $this->logger->info(
        'An update to Terminus is available. Please update to version {version}.',
        array('version' => $cache_data['version'])
      );
    }
  }

  /**
   * Retrieves current version number from repository and saves it to the cache
   *
   * @return [string] $response->name The version number
   */
  private function checkCurrentVersion() {
    $url      = 'https://api.github.com/repos/pantheon-systems/cli/releases?per_page=1';
    $response = Request::send($url, 'GET');   
    $json     = $response->getBody(true);
    $data     = json_decode($json);
    $release  = array_shift($data);
    $this->cache->put_data('latest_release', array('version' => $release->name, 'check_date' => time()));
    return $release->name;
  }

  /**
   * Downloads the given URL to the given target
   *
   * @param [string] $url    Location of file to download
   * @param [string] $target Location to download file to
   * @return [void]
   */
  protected function download($url, $target) {
    try {
      $response = Request::download($url, $target);
      return $target;
    } catch (Exception $e) {
      $this->log()->error($e->getMessage());
    }
  }

  /**
   * @return LoggerInterface
   */
  protected function log() {
    return $this->logger;
  }

  /**
   * @param LoggerInterface $logger
   */
  protected function setLogger($logger) {
    $this->logger = $logger;
  }

  /**
   * @return OutputterInterface
   */
  protected function output() {
    return $this->outputter;
  }

  /**
   * Sends the given message to logger as an error and exits with -1
   *
   * @param [string] $message Message to log as error before exit
   * @param [array]  $context Vars to interpolate in message
   * @return [void]
   */
  protected function failure(
    $message = 'Command failed',
    array $context = array()
  ) {
    throw new TerminusException($message, $context, -1);
  }

  /**
   * @param OutputterInterface $outputter
   */
  protected function setOutputter($outputter) {
    $this->outputter = $outputter;
  }

}
