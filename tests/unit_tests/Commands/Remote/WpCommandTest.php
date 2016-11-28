<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\Remote\WpCommand;

/**
 * Class DrushCommandTest
 *
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class WpCommandTest extends CommandTestCase
{
    protected $command;

    public function testWpCommand()
    {
        $command = $this->getMockBuilder(WpCommand::class)
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
