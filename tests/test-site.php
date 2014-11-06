<?php
use \Terminus\Fixtures;
use \Terminus\SiteFactory;
use \Terminus\Site;

class SiteTest extends PHPUnit_Framework_TestCase {

 function testGetId() {
     $site = SiteFactory::instance('behat-test');
     $this->assertObjectHasAttribute("id",$site);
     $this->assertNotNull($site->getId());
     $this->assertStringMatchesFormat("%a-%a-%a-%a",$site->getId());
 }

 function testGetName() {
   $site = SiteFactory::instance('behat-test');
   $this->assertObjectHasAttribute("name",$site->information);
   $this->assertNotNull($site->getName());
   $this->assertStringMatchesFormat("%a",$site->getName());
   $this->assertEquals('behat-test',$site->getName());
 }

 function testEnvironments() {
   $site = SiteFactory::instance('behat-test');
   $environments = $site->environments();
   $this->assertInstanceOf('\Terminus\EnvironmentDev', $environments->dev);
   $this->assertInstanceOf('\Terminus\EnvironmentTest', $environments->test);
   $this->assertInstanceOf('\Terminus\EnvironmentLive', $environments->live);
 }

 function testInfo() {
   $site = SiteFactory::instance('behat-test');
   $data = $site->info();
   $this->assertNotEmpty($data);
   $this->assertInstanceOf('stdClass', $data);
   $this->assertObjectHasAttribute('name', $data);

 }



}
