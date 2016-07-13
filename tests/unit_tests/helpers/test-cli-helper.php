<?php

use Terminus\Commands\ArtCommand;
use Terminus\Caches\FileCache;
use Terminus\Helpers\CliHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\UpdateHelper
 */
class CliHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var UpdateHelper
   */
  private $cli_helper;

  public function __construct() {
    $command          = new ArtCommand(['runner' => new Runner()]);
    $this->cli_helper = new CliHelper(compact('command'));
  }

  /**
   * @vcr utils#checkCurrentVersion
   */
  public function testCheckCurrentVersion() {
    $current_version = $this->cli_helper->getCurrentVersion();
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
    $this->cli_helper->checkForUpdate(getLogger());
    $file_contents = explode("\n", file_get_contents($log_file));
    $this->assertFalse(
      strpos(array_pop($file_contents), 'An update to Terminus is available.')
    );
    resetOutputDestination($log_file);
  }

  /**
   * @expectedException \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage SSL version is {version}, a minimum version of {min_version} is required.
   */
  public function testRejectsOpenSslLessThanMinimum() {
    // Version 0.9.6b beta 3
    $this->cli_helper->hasMinimumSsl('9461795');
  }

  public function testAcceptsProperOpenSslVersion() {
    // Version 1.0.1c
    $this->assertTrue($this->cli_helper->hasMinimumSsl('268439615'));
  }

  /**
   * @expectedException \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage Error: Terminus requires PHP {min_php} or newer. You are running version {version}.
   */
  public function testRejectsPhpVersionLessThanMinimum() {
    $this->cli_helper->hasMinimumPhp('5.3.3');
  }

  public function testAcceptsProperPhpVersion() {
    $this->assertTrue($this->cli_helper->hasMinimumPhp('5.6.0'));
  }

}
