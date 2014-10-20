<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 class UtilsTest extends PHPUnit_Framework_TestCase {

   function testIsHermes() {
     if( "dashboard.getpantheon.com" === TERMINUS_HOST ) {
       $this->assertTrue(\Terminus\Utils\is_hermes());
     } else {
       $this->assertFalse(\Terminus\Utils\is_hermes());
     }
   }

   function testResultIsMultiObj() {
     $arraysimple = array(
       'name' => 'test',
       'key'  => 'test',
     );

     $arraymultiobj = array(
       0 => (object) array(
         'name' => 'test',
         'key'  => 'test',
       ),
     );

     $this->assertFalse( \Terminus\Utils\result_is_multiobj( $arraysimple ));
     $this->assertTrue( \Terminus\Utils\result_is_multiobj( $arraymultiobj ));
   }

 }
