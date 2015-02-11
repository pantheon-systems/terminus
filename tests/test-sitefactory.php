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

   /**
    * @vcr sitefactory_instance
    */
   function testInstance() {
     $sites = SiteFactory::instance();
     $this->assertTrue(is_array($sites));
     $this->assertNotEmpty($sites);
     $this->assertArrayHasKey('phpunittest', $sites);
     unset($sites);

     $site = SiteFactory::instance('phpunittest');
     $this->assertInstanceOf('\Terminus\Site',$site);
     $this->assertObjectHasAttribute("id",$site);
     $this->assertObjectHasAttribute("information",$site);
     $this->assertObjectHasAttribute("jobs",$site);
     $this->assertObjectHasAttribute("environments",$site);
     $this->assertObjectHasAttribute("id",$site);
   }

 }
