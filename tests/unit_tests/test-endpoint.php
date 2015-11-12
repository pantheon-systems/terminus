<?php
/**
 * Testing class for \Terminus\Endpoint
 *
 */
use \Terminus\Endpoint;
 class EndpointTest extends PHPUnit_Framework_TestCase {

   function testEndpoints() {
      // expected => test
      $tests = array(
        '/api/users/UUID/sites' => array(
                'realm'=>'users','path' => 'sites', 'uuid'=> 'UUID'
        ),
        '/api/products' => array(
                'realm'=>'products','path' => false, 'uuid'=> 'public'
        ),
      );
      foreach( $tests as $expected => $args) {
        $this->assertEquals( $expected, Endpoint::get( $args ) );
      }
    }
}
