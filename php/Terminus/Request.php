<?php

namespace Terminus;

use Terminus;
use Guzzle\Http\Client as Browser;

/**
 * Handles requests made by terminus
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * ( the HTTP library Terminus uses ). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 */

class Request {
  public $request; // Guzzle\Http\Message\Request object
  public $browser; // Guzzle\Http\Client object
  public $response; // most recent Guzzle\Http\Message\Response
  public $responses = array();

  public static function send($url, $method, $data = array()) {

    // create a new Guzzle\Http\Client
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

    $request = $browser->createRequest($method, $url, null, null, $options );

    if( !empty($data['postdata']) ) {
      foreach( $data['postdata'] as $k=>$v ) {
        $request->setPostField($k,$v);
      }
    }

    if( !empty($data['cookies']) ) {
      foreach( $data['cookies'] as $k => $v ) {
        $request->addCookie($k,$v);
      }
    }

    if( !empty($data['headers']) ) {
      foreach( $data['headers'] as $k => $v ) {
        $request->setHeader($k,$v);
      }
    }

    if (Terminus::getConfig("debug")) {
      $debug = "#### REQUEST ####".PHP_EOL;
      $debug .= $request->getRawHeaders();
      Terminus::getLogger()->debug($debug);
      if (isset($data['body'])) {
        Terminus::getLogger()->debug($data['body']);
      }
    }

    $response = $request->send();

    return $response;
  }

  static function userAgent() {
    $agent = sprintf("Terminus/%s (php_version=%s&script=%s)", constant('TERMINUS_VERSION'), phpversion(), constant('TERMINUS_SCRIPT'));
    return $agent;
  }

  public static function download($url, $target) {
    if (file_exists($target)) {
      throw new \Exception(sprintf("Target file (%s) already exists.", $target));
    }

    $handle = fopen($target, 'w');
    $client = new Browser('', array(
      Browser::CURL_OPTIONS => array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_FILE' => $handle,
        'CURLOPT_ENCODING' => 'gzip',
      )
    ));
    $client->get($url)->send();
    fclose($handle);

    return true;
  }

 }
