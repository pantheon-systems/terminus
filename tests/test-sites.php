<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 class SitesTest extends PHPUnit_Framework_TestCase {

   function testSitesShow() {
     require_once CLI_ROOT.'/php/commands/sites.php';
     // this takes the place of the global argv
     $sites = new Sites_Command();
     $data = $sites->show(array(),array('json'));
     print_r($data);
   }

 }
