<?php

use Terminus\Utils;

/**
 * Testing class for Terminus\Utils
 */
class UtilsTest extends PHPUnit_Framework_TestCase {

  public function testIsTest() {
    $this->assertTrue(Utils\isTest());

    putenv('CLI_TEST_MODE=');
    putenv("TERMINUS_TEST_IGNORE=1");
    putenv("VCR_CASSETTE=1");
    $this->assertFalse(Utils\isTest());
    putenv("TERMINUS_TEST_IGNORE=");
    putenv('CLI_TEST_MODE=1');
    putenv("VCR_CASSETTE=");
  }

  public function testIsWindows() {
    $os         = shell_exec('uname');
    $is_windows = Utils\isWindows();
    $this->assertEquals(strpos($os, 'NT') !== false, $is_windows);

    putenv("TERMINUS_TEST_IGNORE=1");
    $this->assertTrue(Utils\isWindows());
    putenv("TERMINUS_TEST_IGNORE=");
  }

}
