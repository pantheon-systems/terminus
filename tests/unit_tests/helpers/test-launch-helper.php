<?php

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\LaunchHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\LaunchHelper
 */
class LaunchHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var LaunchHelper
   */
  private $launch_helper;

  public function __construct() {
    $command             = new ArtCommand(['runner' => new Runner()]);
    $this->launch_helper = new LaunchHelper(compact('command'));
  }

  public function testGetPhpBinary() {
    $php_binary = PHP_BINARY;

    putenv("TERMINUS_PHP_USED=$php_binary");
    $this->testLaunchSelf();

    putenv("TERMINUS_PHP_USED=");
    putenv("TERMINUS_PHP=$php_binary");
    $this->testLaunchSelf();

    putenv("TERMINUS_PHP=");
    $this->testLaunchSelf();
  }

  public function testLaunch() {
    $file_name = '/tmp/output';
    //Testing a good command
    setOutputDestination($file_name);
    $return = $this->launch_helper->launch(
      ['command' => "ls tests/ > $file_name"]
    );
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, 'unit_tests') !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);

    //Testing a bad command
    setOutputDestination($file_name);
    $return = $this->launch_helper->launch(
      ['command' => "exit 1 > $file_name", 'exit_on_error' => false]
    );
    $output = retrieveOutput($file_name);
    $this->assertEquals($return, 1);
    resetOutputDestination($file_name);
  }

  public function testLaunchSelf() {
    $file_name = '/tmp/output';
    //Testing the library route
    setOutputDestination($file_name);
    $return = $this->launch_helper->launchSelf(
      ['command' => "art unicorn > $file_name"]
    );
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, "<.'_.''") !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);

    //Testing the command-line route
    setOutputDestination($file_name);
    $GLOBALS['argv'] = [__DIR__ . '/../../../php/boot-fs.php'];
    $return = $this->launch_helper->launchSelf(
      ['command' => "art unicorn > $file_name"]
    );
    $output = retrieveOutput($file_name);
    $this->assertTrue(strpos($output, "<.'_.''") !== false);
    $this->assertEquals($return, 0);
    resetOutputDestination($file_name);
  }

}
