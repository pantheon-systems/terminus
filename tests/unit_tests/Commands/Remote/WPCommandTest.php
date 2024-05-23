<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = $this->getMockBuilder(WPCommand::class)
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
     * Tests the wp command
     */
    public function testWPCommand()
    {
        $command_output = 'command output';

        $this->command->expects($this->once())
            ->method('prepareEnvironment')
            ->with($this->equalTo('dummy-site.dummy-env'));
        $this->command->expects($this->once())
            ->method('executeCommand')
            ->willReturn($command_output);

        $output = $this->command->wpCommand('dummy-site.dummy-env', ['wpcli', 'command', 'arguments']);
        $this->assertEquals($command_output, $output);
    }

    /**
     * Tests the wp command with retry option
     */
    public function testWPCommandWithRetry()
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

        $output = $this->command->wpCommand('dummy-site.dummy-env', ['wpcli', 'command', 'arguments'], $retry_options);
        $this->assertEquals($command_output, $output);
    }
}
