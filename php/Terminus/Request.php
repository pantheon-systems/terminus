<?php

namespace Terminus;

use Terminus;
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
   * Sends a request to the API
   *
   * @param [string] $uri        URL for API request
   * @param [string] $method     Request method (i.e. PUT, POST, DELETE, or GET)
   * @param [array]  $arg_params Request parameters
   * @return [\Requests] $request
   */
  public static function send($uri, $method, array $arg_params = array()) {
    $extra_params = array(
      'headers'         => array(
        'User-Agent'    => self::userAgent(),
        'Content-type'  => 'application/json',
      ),
      'data'            => array(),
    );
    $params  = array_merge_recursive($extra_params, $arg_params);
    $params['data'] = json_encode($params['data']);

    Terminus::getLogger()->debug(
      "#### REQUEST ####\nParams: {params}\nURI: {uri}\nMethod: {method}",
      array(
        'params' => $params['data'],
        'uri'    => $uri,
        'method' => $method
      )
    );

    $method  = strtolower($method);
    $request = false;
    try {
      $request = \Requests::$method($uri, $params['headers'], $params['data']);
    } catch (\Exception $e) {
      /*
       * Due to the way the Pantheon API formats its responses, this call will
       * always throw a benign error.
       */
    }
    if (!is_object($request)) {
      var_dump($request);
      die();
    }
    var_dump($request->body);
    var_dump($request->headers);
    var_dump($request->status_code);

    return $request;
  }

  /**
   * Download file from target URL
   *
   * @param [string] $url    URL to download from
   * @param [string] $target Target file's name
   * @return [boolean] True if download succeeded
   */
  static function download($url, $target) {
    if (file_exists($target)) {
      throw new TerminusException(
        'Target file {target} already exists.',
        compact('target')
      );
    }

    $handle = fopen($target, 'w');
    $client = new Client(
      '',
      array(
        Client::CURL_OPTIONS => array(
          'CURLOPT_RETURNTRANSFER' => true,
          'CURLOPT_FILE'           => $handle,
          'CURLOPT_ENCODING'       => 'gzip',
        )
      )
    );
    $client->get($url)->send();
    fclose($handle);

    return true;
  }

  /**
   * Sets up and fills a cookie jar
   *
   * @param [array] $params Request data to fill jar with
   * @return [GuzzleHttp\Cookie\CookieJar] $jar
   */
  static function fillCookieJar($params) {
    $jar = new CookieJar();
    $cookies = array();
    if ($session = Session::instance()->get('session', false)) {
      $cookies['X-Pantheon-Session'] = $session; 
    }
    if (isset($params['cookies'])) {
      $cookies = array_merge($cookies, $params['cookies']);
    }
    $jar->fromArray($cookies, '');
    return $jar;
  }

  /**
   * Parses the base URI for requests
   *
   * @return [string] $base_uri
   */
  static function getBaseUri() {
    $base_uri = sprintf(
      '%s://%s:%s',
      TERMINUS_PROTOCOL,
      TERMINUS_HOST,
      TERMINUS_PORT
    );
    return $base_uri;
  }

  /**
   * Enables http_build_query to accept a string as its first argument, as
   * necessitated by some API calls which require only a string in the body.
   *
   * @return [boolean] True if override is successful
   */
  static function overrideHttpBuildQuery() {
    $function_overridden = runkit_function_redefine(
      'http_build_query',
      '$formdata,$numeric_prefix',
      'return json_encode($formdata);'
    );
    return $function_overridden;
  }

  /**
   * Gives the user-agent string
   *
   * @return [string] $agent
   */
  static function userAgent() {
    $agent = sprintf(
      'Terminus/%s (php_version=%s&script=%s)',
      constant('TERMINUS_VERSION'),
      phpversion(),
      constant('TERMINUS_SCRIPT')
    );
    return $agent;
  }

}
