<?php

/**
 * Testing class for Terminus\FileCache
 */
class FileCacheTest extends PHPUnit_Framework_TestCase {

  /**
   * @var FileCache
   */
  private $file_cache;

  public function __construct() {
    $this->file_cache = Terminus::getCache();
  }

  public function testClean() {
    $home      = getenv('HOME');
    $dir_name  = "$home/.terminus/cache";
    $file_name = "$dir_name/testfile";
    exec("touch -t 200401230000 $file_name");
    $old_count = count(scandir($dir_name));
    $this->file_cache->clean();
    $new_count = count(scandir($dir_name));
    $this->assertTrue($new_count < $old_count);

    $this->file_cache->clean();
    $new_new_count = count(scandir($dir_name));
    $this->assertTrue($new_count == $new_new_count);
  }

  public function testExport() {

  }

  public function testFlush() {

  }

  public function testGetData() {

  }

  public function testGetRoot() {

  }

  public function testHas() {

  }

  public function testImport() {

  }

  public function testIsEnabled() {

  }

  public function testPutData() {

  }

  public function testRead() {

  }

  public function testRemove() {

  }

  public function testWrite() {

  }

}
