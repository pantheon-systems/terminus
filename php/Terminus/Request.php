<?php
namespace Terminus;

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

  public static function send( $url, $method, $data = array() ) {
    $browser = new Browser;
    $request = $browser->createRequest($method, $url, null, null, array('allow_redirects' => false ) );
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

    $response = $request->send();
    return $response;
  }

 }
