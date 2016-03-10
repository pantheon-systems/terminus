<?php

use Terminus\Commands\ArtCommand;
use Terminus\Caches\FileCache;
use Terminus\Helpers\UpdateHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\UpdateHelper
 */
class UpdateHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var UpdateHelper
   */
  private $update_helper;

  public function __construct() {
    $command             = new ArtCommand(['runner' => new Runner()]);
    $this->update_helper = new UpdateHelper(compact('command'));
  }

  /**
   * @vcr utils#checkCurrentVersion
   */
  public function testCheckCurrentVersion() {
    $current_version = $this->update_helper->getCurrentVersion();
    preg_match("/\d+\.\d+\.\d+/", $current_version, $matches);
    $this->assertEquals(count($matches), 1);
  }

  /**
   * @vcr utils#checkCurrentVersion
   */
  public function testCheckForUpdate() {
    $log_file = getLogFileName();
    setOutputDestination($log_file);
    $cache = new FileCache();
    $cache->putData(
      'latest_release',
      ['check_date' => strtotime('8 days ago')]
    );
    $this->update_helper->checkForUpdate(getLogger());
    $file_contents = explode("\n", file_get_contents($log_file));
    $this->assertFalse(
      strpos(array_pop($file_contents), 'An update to Terminus is available.')
    );
    resetOutputDestination($log_file);
  }

}