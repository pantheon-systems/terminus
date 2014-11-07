<?php
/**
 * Testing class for \Terminus\Endpoint
 *
 */
use \Terminus\Endpoint;
 class EndpointTest extends PHPUnit_Framework_TestCase {

   function testEndpoints() {
				$host = getenv("TERMINUS_HOST") ?: 'dashboard.getpantheon.com';
				// expected => test
				$tests = array(
				 'https://'.$host.'/api/users/UUID/sites' => array(
					 'realm'=>'users','path' => 'sites', 'uuid'=> 'UUID'
				 ),
				 'https://'.$host.'/api/products' => array(
					 'realm'=>'products','path' => false, 'uuid'=> 'public'
				 ),
				);
				foreach( $tests as $expected => $args) {
				 $this->assertEquals( $expected, Endpoint::get( $args ) );
				}
	}
 }
