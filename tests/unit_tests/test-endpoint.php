<?php
/**
 * Testing class for \Terminus\Endpoint
 *
 */
use \Terminus\Endpoint;
 class EndpointTest extends PHPUnit_Framework_TestCase {

   function testEndpoints() {
      $host = getenv("TERMINUS_HOST") ?: 'dashboard.pantheon.io';
      $protocol = getenv("TERMINUS_PROTOCOL") ?: 'https';
      $port = getenv("TERMINUS_PORT") ?: '443';
      // expected => test
      $tests = array(
        "$protocol://$host:$port/api/users/UUID/sites" => array(
                'realm'=>'users','path' => 'sites', 'uuid'=> 'UUID'
        ),
        "$protocol://$host:$port/products" => array(
                'realm'=>'products','path' => false, 'uuid'=> 'public'
        ),
      );
      foreach( $tests as $expected => $args) {
        $this->assertEquals( $expected, Endpoint::get( $args ) );
      }
    }
}
