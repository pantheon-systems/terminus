<?php

namespace Terminus;

use Terminus;
use Terminus\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\RequestOptions;
use Terminus\Exceptions\TerminusException;

/**
 * Handles requests made by terminus
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * (the HTTP library Terminus uses). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 */

class Request {

  /**
   * A list of fields not to display values for in output
   * @var array
   * TODO: Move this logic to the logger
   */
  protected static $blacklist = array('password');

  /**
   * Download file from target URL
   *
   * @param string $url    URL to download from
   * @param string $target Target file's name
   * @return bool True if download succeeded
   */
  public static function download($url, $target) {
    if (file_exists($target)) {
      throw new TerminusException(
        'Target file {target} already exists.',
        compact('target')
      );
    }

    try {
      $client   = new Client();
      $response = $client->request('GET', $url, array('sink' => $target));
    } catch (\Exception $e) {
      throw new TerminusException($e->getMessage(), array(), 1);
    }
    return true;
  }

  /**
   * Make a request to the Dashbord's internal API
   *
   * @param string $path    API path (URL)
   * @param array  $options Options for the request
   *   [string] method GET is default
   *   [mixed]  data   Native PHP data structure (e.g. int, string array, or
   *     simple object) to be sent along with the request. Will be encoded as
   *     JSON for you.
   * @return array
   */
  public function pagedRequest($path, array $options = array()) {
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

      $resp = $this->simpleRequest($paged_path);

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
   * Make a request to the Pantheon API
   *
   * @param string      $realm   Permissions realm for data request (e.g. user,
   *   site organization, etc. Can also be "public" to simply pull read-only
   *   data that is not privileged.
   * @param string      $uuid    The UUID of the item in the realm to access
   * @param string|bool $path    API path (URL)
   * @param string      $method  HTTP method to use
   * @param mixed       $options A native PHP data structure (e.g. int, string,
   *   array, or stdClass) to be sent along with the request
   * @return array
   * @throws TerminusException
   */
  public function request(
    $realm,
    $uuid,
    $path    = false,
    $method  = 'GET',
    $options = array()
  ) {
    $logger = Terminus::getLogger();

    try {
      $url = Endpoint::get(
        array(
          'realm' => $realm,
          'uuid'  => $uuid,
          'path'  => $path,
        )
      );
      $logger->debug('Request URL: ' . $url);
      // Turn off cert verification if we are sending to a Onebox.
      // Is there a better place to do this?
      if(TRUE) {
        $options += [RequestOptions::VERIFY => false];
      }
      $response = $this->send($url, $method, $options);

      $data = array(
        'data'        => json_decode($response->getBody()->getContents()),
        'headers'     => $response->getHeaders(),
        'status_code' => $response->getStatusCode(),
      );
      return $data;
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
      $response = $e->getResponse();
      throw new TerminusException($response->getBody(true));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      $request = $e->getRequest();
      $sanitized_request = Utils\stripSensitiveData(
        (string)$request,
        self::$blacklist
      );
      throw new TerminusException(
        'API Request Error. {msg} - Request: {req}',
        array('req' => $sanitized_request, 'msg' => $e->getMessage())
      );
    } catch (\Exception $e) {
      throw new TerminusException(
        'API Request Error: {msg}',
        array('msg' => $e->getMessage())
      );
    }
  }

  /**
   * Simplified request method for Pantheon API
   *
   * @param string $path        API path (URL)
   * @param array  $arg_options Options for the request
   *   [string] method        GET is default
   *   [mixed]  data          Native PHP data structure (e.g. int, string
   *     array, or simple object) to be sent along with the request. Will
   *     be encoded as JSON for you.
   *   [boolean] absolute_url True if URL passed is to be treated as absolute
   * @return array
   * @throws TerminusException
   */
  public function simpleRequest($path, $arg_options = array()) {
    $default_options = array(
      'method'       => 'get',
      'absolute_url' => false,
    );
    $options = array_merge($default_options, $arg_options);

    $url = $path;
    if ((strpos($path, 'http') !== 0) && !$options['absolute_url']) {
      $url = sprintf(
        '%s://%s:%s/api/%s',
        TERMINUS_PROTOCOL,
        TERMINUS_HOST,
        TERMINUS_PORT,
        $path
      );
    }

    try {
      Terminus::getLogger()->debug('URL: {url}', compact('url'));
      $response = $this->send($url, $options['method'], $options);
    } catch (\GuzzleHttp\Exception\BadResponseException $e) {
      throw new TerminusException(
        'API Request Error: {msg}',
        array('msg' => $e->getMessage())
      );
    }

    $data = array(
      'data'        => json_decode($response->getBody()->getContents()),
      'headers'     => $response->getHeaders(),
      'status_code' => $response->getStatusCode(),
    );
    return $data;
  }

  /**
   * Sends a request to the API
   *
   * @param string $uri        URL for API request
   * @param string $method     Request method (i.e. PUT, POST, DELETE, or GET)
   * @param array  $arg_params Request parameters
   * @return \Psr\Http\Message\ResponseInterface
   */
  private function send($uri, $method, array $arg_params = array()) {
    $extra_params = array(
      'headers'         => array(
        'User-Agent'    => $this->userAgent(),
        'Content-type'  => 'application/json',
      )
    );

    if ($session = Session::instance()->get('session', false)) {
      $extra_params['headers']['Authorization'] = "Bearer $session";
    }
    $params = array_merge_recursive($extra_params, $arg_params);
    if (isset($params['form_params'])) {
      $params['json'] = $params['form_params'];
      unset($params['form_params']);
    }

    $client = new Client(
      array(
        'base_uri' => $this->getBaseUri(),
        'cookies'  => $this->fillCookieJar($params)
      )
    );
    unset($params['cookies']);

    Terminus::getLogger()->debug(
      "#### REQUEST ####\nParams: {params}\nURI: {uri}\nMethod: {method}",
      array(
        'params' => json_encode($params),
        'uri'    => $uri,
        'method' => $method
      )
    );

    //Required objects and arrays stir benign warnings.
    error_reporting(E_ALL ^ E_WARNING);
    $request = new HttpRequest(ucwords($method), $uri, $params);
    error_reporting(E_ALL);
    $response = $client->send($request, $params);

    return $response;
  }

  /**
   * Sets up and fills a cookie jar
   *
   * @param array $params Request data to fill jar with
   * @return \GuzzleHttp\Cookie\CookieJar $jar
   */
  private function fillCookieJar(array $params) {
    $jar     = new CookieJar();
    $cookies = array();
    if (isset($params['cookies'])) {
      $cookies = array_merge($cookies, $params['cookies']);
    }
    $jar->fromArray($cookies, '');
    return $jar;
  }

  /**
   * Parses the base URI for requests
   *
   * @return string
   */
  private function getBaseUri() {
    $base_uri = sprintf(
      '%s://%s:%s',
      TERMINUS_PROTOCOL,
      TERMINUS_HOST,
      TERMINUS_PORT
    );
    return $base_uri;
  }

  /**
   * Gives the user-agent string
   *
   * @return string
   */
  private function userAgent() {
    $agent = sprintf(
      'Terminus/%s (php_version=%s&script=%s)',
      constant('TERMINUS_VERSION'),
      phpversion(),
      constant('TERMINUS_SCRIPT')
    );
    return $agent;
  }

}
