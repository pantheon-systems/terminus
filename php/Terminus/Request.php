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
    $options = array(
      'allow_redirects' => false,
      'verify'          => false,
      'json'            => false
    );
    if (isset($data['allow_redirects'])) {
      $options['allow_redirects'] = $data['allow_redirects'];
    }
    if (isset($data['json'])) {
      $options['json'] = $data['json'];
    }
    if (isset($data['body']) && $data['body']) {
      $options['body'] = $data['body'];
      Terminus::getLogger()->debug($data['body']);
    }

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

    if (Terminus::getConfig('debug')) {
      $debug  = '#### REQUEST ####' . PHP_EOL;
      $debug .= $request->getRawHeaders();
      Terminus::getLogger()->debug($debug);
      if (isset($data['body'])) {
        Terminus::getLogger()->debug($data['body']);
      }
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
        sprintf('Target file (%s) already exists.', $target)
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
