<?php
/**
 * Testing class for \Terminus\Endpoint
 *
 */
use \Terminus\Endpoint;
 class EndpointTest extends PHPUnit_Framework_TestCase {

   function testEndpoints() {
     if( \Terminus\Utils\is_hermes() ) {
       // expected => test
       $tests = array(
         'https://dashboard.getpantheon.com/api/users/UUID/sites' => array(
           'realm'=>'user','path' => 'sites', 'uuid'=> 'UUID'
         ),
         'https://dashboard.getpantheon.com/api/products' => array(
           'realm'=>'products','path' => false, 'uuid'=> 'public'
         ),
       );
       foreach( $tests as $expected => $args) {
         $this->assertEquals( $expected, Endpoint::get( $args ) );
       }
     } else {
       $this->assertEquals( 'https://terminus.getpantheon.com/terminus.php?user=UUID&path=sites', Endpoint::get( array('realm'=>'user','path' => 'sites', 'uuid'=> 'UUID') ) );
     }
   }

 }
