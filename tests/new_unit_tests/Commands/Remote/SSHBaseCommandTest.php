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

    public function testValidateConnectionModeGitWarning()
    {
        // fake out ssh command
        $this->environment->expects($this->once())
            ->method('sendCommandViaSsh')
            ->with($this->equalTo('dummy arg1 arg2'))
            ->willReturn(['output' => 'dummy output', 'exit_code' => 0]);

        // trigger git connection mode warning
        $this->environment->expects($this->once())
            ->method('info')
            ->with($this->equalTo('connection_mode'))
            ->willReturn('git');

        //expected info and error messages
        $this->logger->expects($this->exactly(2))
            ->method('log')->withConsecutive(
                [
                    $this->equalTo('warning'),
                    $this->stringContains('Note: This environment is in read-only Git mode.')
                ],
                [$this->equalTo('info'), $this->stringContains('Command:')]
            );

        $this->command->dummyCommand('dummy-site.dummy-env', ['arg1', 'arg2']);
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
}
