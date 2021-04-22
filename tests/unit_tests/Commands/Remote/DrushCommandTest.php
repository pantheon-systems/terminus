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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = $this->getMockBuilder(DrushCommand::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'prepareEnvironment',
                'executeCommand',
            ])
            ->getMock();
    }

    /**
     * Tests the drush command
     */
    public function testDrushCommand()
    {
        $command_output = 'command output';

        $this->command->expects($this->once())
            ->method('prepareEnvironment')
            ->with($this->equalTo('dummy-site.dummy-env'));
        $this->command->expects($this->once())
            ->method('executeCommand')
            ->willReturn($command_output);

        $output = $this->command->drushCommand('dummy-site.dummy-env', ['drushable', 'command', 'arguments',]);
        $this->assertEquals($command_output, $output);
    }
}
