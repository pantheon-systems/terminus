<?php

namespace Terminus;

use Terminus;
use Guzzle\Http\Client as Browser;
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
   * @var [Guzzle\Http\Client] $browser
   */
  public $browser;

  /**
   * @var [Guzzle\Http\Message\Request] $browser
   */
  public $request;

  /**
   * @var [Guzzle\Http\Message\Response] $response
   */
  public $response;

  /**
   * @var [array] $responses A collection of $response items
   */
  public $responses = array();

  /**
   * Sends a request to the API
   *
   * @param [string] $url    URL for API request
   * @param [string] $method Request method (i.e. PUT, POST, DELETE, or GET)
   * @param [array]  $data   Options for request
   * @return [Guzzle\Http\Message\Response] $response
   */
  public static function send($url, $method, $data = array()) {
    // Create a new Guzzle\Http\Client
    $browser = new Browser;
    $browser->setUserAgent(self::userAgent());
    $options = self::processOptions($data);

    $request = $browser->createRequest($method, $url, null, null, $options);

    if (!empty($data['postdata'])) {
      foreach ($data['postdata'] as $k=>$v) {
        $request->setPostField($k, $v);
      }
    }

    if (!empty($data['cookies'])) {
      foreach ($data['cookies'] as $k => $v) {
        $request->addCookie($k, $v);
      }
    }

    if (!empty($data['headers'])) {
      foreach ($data['headers'] as $k => $v) {
        $request->setHeader($k, $v);
      }
    }

    if (!empty($data['body']) && method_exists($request, 'setBody')) {
      $request->setBody(json_encode($data['body']));
    }

    $debug  = '#### REQUEST ####' . PHP_EOL;
    $debug .= $request->getRawHeaders();
    Terminus::getLogger()->debug(
      'Headers: {headers}',
      array('headers' => $debug)
    );
    if (isset($data['body'])) {
      Terminus::getLogger()->debug(
        'Body: {body}',
        array('body' => $data['body'])
      );
    }

    $response = $request->send();

    return $response;
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
        'Target file {target} already exists.', compact('target')
      );
    }

    $handle = fopen($target, 'w');
    $client = new Browser(
      '',
      array(
        Browser::CURL_OPTIONS => array(
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
   * Merges the given options with defaults to ensure necessary fields exist
   *
   * @param [array] $arg_options Options for a request
   * @return [array] $options Completed options array
   */
  static function processOptions($arg_options) {
    $default_options = array(
      'headers' => array(
        'Content-type' => 'application/json',
      ),
      'verify'  => false,
    );
    $options = array_merge($arg_options, $default_options);
    if (isset($options['data'])) {
      $options['body'] = $options['data'];
    }
    if (isset($options['body'])) {
      $options['body'] = json_encode($options['body']);
    }
    return $options;
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
