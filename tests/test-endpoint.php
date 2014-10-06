<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
use \Terminus\Endpoint;
 class EndpointTest extends PHPUnit_Framework_TestCase {

   function testEndpoints() {
     if( "dashboard.getpantheon.com" === TERMINUS_HOST ) {
       $this->assertEquals( 'https://dashboard.getpantheon.com/api/user/UUID/sites', Endpoint::get( array('realm'=>'user','path' => 'sites', 'uuid'=> 'UUID') ) );
     } else {
       $this->assertEquals( 'https://dashboard.getpantheon.com/terminus.php?user=UUID&path=sites', Endpoint::get( array('realm'=>'user','path' => 'sites', 'uuid'=> 'UUID') ) );
     }
   }

 }
