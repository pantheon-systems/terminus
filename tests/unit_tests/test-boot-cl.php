<?php

require_once __DIR__ . '/../../php/boot-cl.php';

/**
 * Testing class for boot-cl.php
 */
class BootClTest extends PHPUnit_Framework_TestCase {

  public function testGetVendorPaths() {
    $vendor_paths = getVendorPaths();
    $this->assertInternalType('array', $vendor_paths);
    foreach ($vendor_paths as $path) {
      $this->assertTrue(strpos($path, TERMINUS_ROOT) === 0);
      $this->assertTrue(strpos($path, '/vendor') !== false);
    }
  }

  public function testLoadDependencies() {
    $file_name = TERMINUS_ROOT . '/vendor/autoload.php';
    loadDependencies();
    $included_files = get_included_files();
    $is_included = array_search($file_name, $included_files) !== false;
    $this->assertTrue($is_included);
  }

}