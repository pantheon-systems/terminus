<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\Remote\DrushCommand;

/**
 * Class DrushCommandTest
 * Testing class for Pantheon\Terminus\Commands\Remote\DrushCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class DrushCommandTest extends CommandTestCase
{
    /**
     * Tests the drush command
     */
    public function testDrushCommand()
    {
        $command = $this->getMockBuilder(DrushCommand::class)
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

        $output = $command->drushCommand('dummy-site.dummy-env', ['drushable', 'command', 'arguments']);
        $this->assertEquals('command output', $output);
    }
}
