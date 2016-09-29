<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * SSHBaseCommand Test Suite
 *
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class SSHBaseCommandTest extends CommandTestCase
{
    protected $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = new DummyCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    public function testExecuteCommand()
    {
        // fake out ssh command
        $this->environment->expects($this->once())
            ->method('sendCommandViaSsh')
            ->with($this->equalTo('dummy arg1 arg2'))
            ->willReturn(['output' => 'dummy output', 'exit_code' => 0]);

        $output = $this->command->dummyCommand('dummy-site.dummy-env', ['arg1', 'arg2']);

        $this->assertEquals('dummy output', $output);
    }

    public function testUnavailableWithSuggestion()
    {
        //expected info and error messages
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('error'),
                $this->equalTo(
                    'That command is not available via Terminus. '
                    . 'Please use the native {command} command. '
                    . 'Hint: You may want to try `{suggestion}`.'
                ),
                $this->equalTo([
                    'command'    => 'dummy',
                    'suggestion' => "terminus alternative"
                ])
            );

        $output = $this->command->dummyCommand('dummy-site.dummy-env', ['avoided']);

        $this->assertEquals('', $output);
    }

    public function testUnavailableWithoutSuggestion()
    {
        //expected info and error messages
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('error'),
                $this->equalTo(
                    'That command is not available via Terminus. '
                    . 'Please use the native {command} command.'
                ),
                $this->equalTo([
                    'command' => 'dummy',
                ])
            );

        $output = $this->command->dummyCommand('dummy-site.dummy-env', ['no-alternative']);

        $this->assertEquals('', $output);
    }

    public function testValidateConnectionModeGit()
    {
        // should warn about git mode
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->stringContains('This environment is in read-only Git mode.')
            );

        $this->protectedMethodCall($this->command, 'validateConnectionMode', ['git']);
    }
}
