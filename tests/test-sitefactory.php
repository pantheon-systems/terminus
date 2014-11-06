<?php
use \Terminus\Fixtures;
use \Terminus\SiteFactory;
use \Terminus\Site;
use \Symfony\Component\Process\Process;
/**
 * Testing class for \Terminus\Utils
 *
 */
 class SiteFactoryTest extends PHPUnit_Framework_TestCase {

   function testInstance() {
     $sites = SiteFactory::instance();
     $this->assertTrue(is_array($sites));
     $this->assertNotEmpty($sites);
     $this->assertArrayHasKey('behat-test', $sites);
     unset($sites);

     $site = SiteFactory::instance('behat-test');
     $this->assertInstanceOf('\Terminus\Site',$site);
     $this->assertObjectHasAttribute("id",$site);
     $this->assertObjectHasAttribute("information",$site);
     $this->assertObjectHasAttribute("jobs",$site);
     $this->assertObjectHasAttribute("environments",$site);
     $this->assertObjectHasAttribute("id",$site);
   }

 }
