<?php
namespace Terminus;

use Guzzle\Http\Client as Browser;
use \Terminus\Fixtures;
use \Terminus\FauxRequest;
use \Terminus\Loggers\Regular;
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
    if (getenv("USE_FIXTURES") == 1) {
      return FauxRequest::send($url, $method, $data);
    }
    // create a new Guzzle\Http\Client
    $browser = new Browser;
    $options = array();
    $options['allow_redirects'] = @$data['allow_redirects'] ?: false;
    $options['json'] = @$data['json'] ?: false;
    if( @$data['body'] ) {
      $options['body'] = $data['body'];
      if (\Terminus::get_config("debug")) {
        \Terminus\Loggers\Regular::debug($data['body']);
      }
    }
    $options['verify'] = false;

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

    if (\Terminus::get_config("debug")) {
      $debug = "#### REQUEST ####".PHP_EOL;
      $debug .= $request->getRawHeaders();
      \Terminus\Loggers\Regular::debug($debug);
    }

    if ( getenv("BUILD_FIXTURES") ) {
      Fixtures::put("request_headers", $request->getRawHeaders());
    }

    $response = $request->send();

    if ( getenv("BUILD_FIXTURES") ) {
      Fixtures::put(array($url,$method,$data), $response);
    }

    return $response;
  }

  public static function download($url,$target) {
    // @todo use Guzzle in the future, but for now this will do
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    if (curl_error($ch)) {
      return false;
    }
    curl_close($ch);
    if (file_exists($target)) {
      throw new \Exception(sprintf("Target file (%s) already exists.", $target));
    }
    file_put_contents($target, $content, LOCK_EX);
    return true;
  }

 }
