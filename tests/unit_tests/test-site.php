<?php
use \Terminus\SiteFactory;
use \Terminus\Site;

class SiteTest extends PHPUnit_Framework_TestCase {

 /**
  * @vcr site_get_id
  */
 function testGetId() {
     $site = SiteFactory::instance('phpunittest');
     $this->assertObjectHasAttribute("id",$site);
     $this->assertNotNull($site->getId());
     $this->assertStringMatchesFormat("%a-%a-%a-%a",$site->getId());
 }
 /**
  * @vcr site_get_name
  */
 function testGetName() {
   $site = SiteFactory::instance('phpunittest');
   $this->assertObjectHasAttribute("name",$site->information);
   $this->assertNotNull($site->getName());
   $this->assertStringMatchesFormat("%a",$site->getName());
   $this->assertEquals('phpunittest',$site->getName());
 }

 /**
  * @vcr site_environments
  */
 function testEnvironments() {
   $site = SiteFactory::instance('phpunittest');
   $environments = $site->environments();
   $this->assertInstanceOf('\Terminus\EnvironmentDev', $environments->dev);
   $this->assertInstanceOf('\Terminus\EnvironmentTest', $environments->test);
   $this->assertInstanceOf('\Terminus\EnvironmentLive', $environments->live);
 }

 /**
  * @vcr site_info
  */
 function testInfo() {
   $site = SiteFactory::instance('phpunittest');
   $data = $site->info();
   $this->assertNotEmpty($data);
   $this->assertInstanceOf('stdClass', $data);
   $this->assertObjectHasAttribute('name', $data);
 }

 /**
  * @vcr site_attributes
  */
 function testAttributes() {
   $site = SiteFactory::instance('phpunittest');
   $data = $site->attributes();
   $this->assertNotEmpty($data);
   $this->assertInstanceOf('stdClass', $data);
   $this->assertObjectHasAttribute('label', $data);
 }
}
