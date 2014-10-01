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

 }
