<?php

use \Terminus\Upstreams;

class UpstreamsTest extends PHPUnit_Framework_TestCase {

  /**
   * @vcr upstreams_instance
   */
  function testUpstreamsInstance() {
    $upstreams = Upstreams::instance();
    $test      = $upstreams->getById('3b754bc2-48f8-4388-b5b5-2631098d03de');
    $this->assertEquals('CiviCRM Starter Kit', $test['longname']);
    $test = $upstreams->query();
    $this->assertNotEmpty($test);
  }
}
