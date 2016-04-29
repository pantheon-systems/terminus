<?php

use Terminus\Utils;

/**
 * Testing class for Terminus\Utils
 */
class UtilsTest extends PHPUnit_Framework_TestCase {

  public function testIsTest() {
    $this->assertTrue(Utils\isTest());
  }

  public function testIsWindows() {
    $os         = shell_exec('uname');
    $is_windows = Utils\isWindows();
    $this->assertEquals(strpos($os, 'NT') !== false, $is_windows);
  }

}
