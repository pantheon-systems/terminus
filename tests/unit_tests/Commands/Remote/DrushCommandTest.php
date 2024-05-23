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
                'log',
            ])
            ->getMock();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMock();

        $this->command->method('log')->willReturn($this->logger);
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

        $output = $this->command->drushCommand('dummy-site.dummy-env', ['drushable', 'command', 'arguments']);
        $this->assertEquals($command_output, $output);
    }

    /**
     * Tests the drush command with retry option
     */
    public function testDrushCommandWithRetry()
    {
        $command_output = 'command output';
        $retry_options = ['retry' => 3, 'progress' => false];

        $this->command->expects($this->once())
            ->method('prepareEnvironment')
            ->with($this->equalTo('dummy-site.dummy-env'));
        $this->command->expects($this->exactly(3))
            ->method('executeCommand')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \Pantheon\Terminus\Exceptions\TerminusProcessException('First attempt failed')),
                $this->throwException(new \Pantheon\Terminus\Exceptions\TerminusProcessException('Second attempt failed')),
                $this->returnValue($command_output)
            ));

        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->withConsecutive(
                [$this->equalTo('Retry attempt 1 for command failed.')],
                [$this->equalTo('Retry attempt 2 for command failed.')]
            );

        $output = $this->command->drushCommand('dummy-site.dummy-env', ['drushable', 'command', 'arguments'], $retry_options);
        $this->assertEquals($command_output, $output);
    }
}
