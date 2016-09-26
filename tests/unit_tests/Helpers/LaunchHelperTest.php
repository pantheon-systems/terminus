<?php

namespace Terminus\UnitTests\Helpers;

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\LaunchHelper;
use Terminus\UnitTests\TerminusTest;

/**
 * Testing class for Terminus\Helpers\LaunchHelper
 */
class LaunchHelperTest extends TerminusTest
{

  /**
   * @var LaunchHelper
   */
    private $launch_helper;

    public function setUp()
    {
        parent::setUp();
        $command = new ArtCommand(['runner' => $this->runner,]);
        $this->launch_helper = new LaunchHelper(compact('command'));
    }

    public function testLaunch()
    {
        $file_name = '/tmp/output';
        //Testing a good command
        $this->setOutputDestination($file_name);
        $return = $this->launch_helper->launch(
            ['command' => "ls tests/ > $file_name"]
        );
        $output = $this->retrieveOutput($file_name);
        $this->assertTrue(strpos($output, 'unit_tests') !== false);
        $this->assertEquals($return, 0);
        $this->resetOutputDestination($file_name);

        //Testing a bad command
        $this->setOutputDestination($file_name);
        $return = $this->launch_helper->launch(
            ['command' => "exit 1 > $file_name", 'exit_on_error' => false]
        );
        $output = $this->retrieveOutput($file_name);
        $this->assertEquals($return, 1);
        $this->resetOutputDestination($file_name);
    }

    public function testLaunchSelf()
    {
        $file_name = '/tmp/output';
        //Testing the library route
        $this->setOutputDestination($file_name);
        $return = $this->launch_helper->launchSelf(
            ['command' => "art unicorn > $file_name"]
        );
        $output = $this->retrieveOutput($file_name);
        $this->assertTrue(strpos($output, "<.'_.''") !== false);
        $this->assertEquals($return, 0);
        $this->resetOutputDestination($file_name);

        //Testing the command-line route
        $this->setOutputDestination($file_name);
        $GLOBALS['argv'] = [__DIR__ . '/../../../php/boot-fs.php'];
        $return = $this->launch_helper->launchSelf(
            ['command' => "art unicorn > $file_name"]
        );
        $output = $this->retrieveOutput($file_name);
        $this->assertTrue(strpos($output, "<.'_.''") !== false);
        $this->assertEquals($return, 0);
        $this->resetOutputDestination($file_name);
    }
}
