<?php

use Terminus\Caches\FileCache;

/**
 * Testing class for Terminus\Caches\FileCache
 */
class FileCacheTest extends PHPUnit_Framework_TestCase {

  /**
   * @var FileCache
   */
  private $file_cache;

  /**
   * @var string
   */
  private $test_file_name = 'testfile';

  public function __construct() {
    $this->file_cache = new FileCache();
  }

  public function testClean() {
    //Setting a file we know will have to be cleaned
    $home      = getenv('HOME');
    $dir_name  = "$home/.terminus/cache";
    $file_name = "$dir_name/" . $this->test_file_name;
    exec("touch -t 200401230000 $file_name");
    $old_count = count(scandir($dir_name));
    $this->file_cache->clean();
    $new_count = count(scandir($dir_name));
    $this->assertTrue($new_count < $old_count);

    //Running again when nothing new should be removed
    $this->file_cache->clean();
    $new_new_count = count(scandir($dir_name));
    $this->assertTrue($new_count == $new_new_count);
  }

  public function testFlush() {
    //Setting a file so we know something will be removed
    $home      = getenv('HOME');
    $dir_name  = "$home/.terminus/cache";
    $file_name = "$dir_name/" . $this->test_file_name;
    setOutputDestination($file_name);
    $old_count = count(scandir($dir_name));
    $this->file_cache->flush();
    $new_count = count(scandir($dir_name));
    $this->assertTrue($new_count < $old_count);

    //Running again when nothing should be removed
    $this->file_cache->flush();
    $new_new_count = count(scandir($dir_name));
    $this->assertTrue($new_count == $new_new_count);
  }

  public function testGetData() {
    //Trying to get data we know is present
    $file_name = $this->getFileName();
    setOutputDestination($file_name);
    $stamp = 'Laika, primul călător în cosmos';
    file_put_contents($file_name, json_encode($stamp));
    $data = $this->file_cache->getData($this->test_file_name);
    $this->assertEquals($stamp, $data);
    resetOutputDestination($file_name);

    //Trying to get data when the file is not there
    $data = $this->file_cache->getData($this->test_file_name);
    $this->assertInternalType('array', $data);
  }

  public function testGetRoot() {
    $this->assertTrue(is_dir($this->file_cache->getRoot()));
  }

  public function testHas() {
    //Getting a file name of a file we know exists
    $file_name = $this->getFileName();
    setOutputDestination($file_name);
    $this->assertEquals($this->file_cache->has($this->test_file_name), $file_name);
    exec("rm $file_name");

    //Checking for a file name we know doesn't exist
    $this->assertFalse($this->file_cache->has($this->test_file_name));
    resetOutputDestination($file_name);
  }

  public function testIsEnabled() {
    $this->assertTrue($this->file_cache->isEnabled());

    //Cache is disabled when the cache dir DNE
    $file_cache = new FileCache(['cache_dir' => '/invalid/dir']);
    $this->assertFalse($file_cache->isEnabled());
  }

  public function testPutData() {
    $data = ['s' => 4.5895, 'e' => 137.4417];
    $this->file_cache->putData($this->test_file_name, $data);
    $file_name = $this->getFileName();
    $contents  = file_get_contents($file_name);
    $this->assertEquals($contents, json_encode($data));
  }

  public function testRemove() {
    $file_name = $this->getFileName();
    setOutputDestination($file_name);
    $removed = $this->file_cache->remove($this->test_file_name);
    $this->assertTrue($removed);

    $removed = $this->file_cache->remove($this->test_file_name);
    $this->assertFalse($removed);
  }

  private function getFileName() {
    $home      = getenv('HOME');
    $dir_name  = "$home/.terminus/cache";
    $file_name = "$dir_name/" . $this->test_file_name;
    return $file_name;
  }

}
