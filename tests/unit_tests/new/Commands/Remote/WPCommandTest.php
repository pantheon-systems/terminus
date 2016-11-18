<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remove;

use Pantheon\Terminus\Commands\Remote\WPCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class WPCommandTest
 * Testing class for Pantheon\Terminus\Commands\Remote\WPCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class WPCommandTest extends CommandTestCase
{
    /**
     * Tests the wp command
     */
    public function testWPCommand()
    {
        $command = $this->getMockBuilder(WPCommand::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'prepareEnvironment',
                'executeCommand',
            ])
            ->getMock();

        $command->expects($this->once())
            ->method('prepareEnvironment')
            ->with($this->equalTo('dummy-site.dummy-env'));

        $command->expects($this->once())
            ->method('executeCommand')
            ->willReturn('command output');

        $output = $command->wpCommand('dummy-site.dummy-env', ['wpcli', 'command', 'arguments']);
        $this->assertEquals('command output', $output);
    }
}
