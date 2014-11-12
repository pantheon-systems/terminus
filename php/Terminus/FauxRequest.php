<?php
namespace Terminus;

use Guzzle\Http\Client as Browser;
use \Terminus\Fixtures;
/**
 * Handles requests made by terminus
 *
 * This is simply a class to manage the interactions between Terminus and Guzzle
 * ( the HTTP library Terminus uses ). This class should eventually evolve to
 * manage all requests to external resources such. Eventually we could even log
 * requests in debug mode.
 */

class FauxRequest {
  public $request;
  public $browser;
  public $response;
  public $responses = array();

  public static function send( $url, $method, $data = array() ) {
    return Fixtures::get(array($url,$method,$data));
  }

 }
