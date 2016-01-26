<?php

use Terminus\Models\Collections\Upstreams;

class UpstreamsTest extends PHPUnit_Framework_TestCase {

  /**
   * @vcr upstreams_instance
   */
  public function testUpstreamsInstance() {
    $upstreams = new Upstreams(array());
    $test      = $upstreams->get('3b754bc2-48f8-4388-b5b5-2631098d03de');
    $this->assertEquals('CiviCRM Starter Kit', $test->get('longname'));
    $test = $upstreams->all();
    $this->assertNotEmpty($test);
  }

}
