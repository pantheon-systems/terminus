<?php

namespace Pantheon\Terminus\UnitTests\Commands\Remote;

use League\Container\Container;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\ProcessUtils;

/**
 * SSHBaseCommand Test Suite
 * Testing class for Pantheon\Terminus\Commands\Remote\SSHBaseCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class SSHBaseCommandTest extends CommandTestCase
{
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine_helper;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->method('get')
            ->with(LocalMachineHelper::class)
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->method('exec_interactive')
            ->willReturn(['output' => 'output', 'exit_code' => 0,]);

        $this->command = new DummyCommand();
        $this->command->setConfig($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setContainer($this->container);
    }

    /**
     * Tests command execution under normal circumstances
     */
    public function testExecuteCommand()
    {
        $options = ['arg1', 'arg2', '<escape me>',];
        $site_name = 'site name';
        $mode = 'sftp';

        $this->environment->expects($this->once())
            ->method('isDevelopment')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]')
            );
        $this->environment->expects($this->once())
            ->method('sftpConnectionInfo')
            ->willReturn(['username' => 'THE USER NAME', 'host' => 'THE HOST', 'port' => 'THE PORT',]);

        $out = $this->command->dummyCommand("$site_name.env", $options);
        $this->assertNull($out);
    }

    /**
     * Tests command execution when exiting with a nonzero status
     */
    public function testExecuteCommandNonzeroStatus()
    {
        $dummy_output = 'dummy output';
        $options = ['arg1', 'arg2',];
        $site_name = 'site name';
        $mode = 'sftp';
        $status_code = 1;
        $this->environment->id = 'env_id';
        $sftp_info = [
            'host' => 'THE HOST',
            'port' => 'THE PORT',
            'username' => 'THE USER NAME',
        ];
        $return_data = ['output' => $dummy_output, 'exit_code' => $status_code,];

        $expectedLoggedCommand = 'dummy arg1 arg2';

        $this->environment->expects($this->once())
            ->method('isDevelopment')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->environment->expects($this->once())
            ->method('sftpConnectionInfo')
            ->with()
            ->willReturn($sftp_info);
        $this->local_machine_helper->expects($this->once())
            ->method('execute')
            ->willReturn($return_data);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]'),
                $this->equalTo([
                    'site' => $site_name,
                    'env' => $this->environment->id,
                    'command' => "$expectedLoggedCommand",
                    'exit' => $status_code,
                ])
            );

        $this->setExpectedException(TerminusProcessException::class, $dummy_output);

        $out = $this->command->dummyCommand("$site_name.{$this->environment->id}", $options);
        $this->assertNull($out);
    }

    /**
     * Tests command execution when in git mode
     */
    public function testExecuteCommandInGitMode()
    {
        $options = ['arg1', 'arg2', '--secret', 'somesecret'];
        $site_name = 'site name';
        $mode = 'git';
        $status_code = 0;
        $sftp_info = [
            'host' => 'THE HOST',
            'port' => 'THE PORT',
            'username' => 'THE USER NAME',
        ];

        $expectedLoggedCommand = 'dummy arg1 arg2';

        $this->environment->expects($this->once())
            ->method('isDevelopment')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('get')
            ->with($this->equalTo('connection_mode'))
            ->willReturn($mode);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo(
                    'This environment is in read-only Git mode. If you want to make changes to the codebase of this site '
                    . '(e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first.'
                )
            );
        $this->site->expects($this->any())->method('get')
            ->withConsecutive(
                [$this->equalTo('name'),],
                [$this->equalTo('name'),]
            )
            ->willReturnOnConsecutiveCalls($site_name, $site_name);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Command: {site}.{env} -- {command} [Exit: {exit}]'),
                $this->equalTo([
                    'site' => $site_name,
                    'env' => $this->environment->id,
                    'command' => "$expectedLoggedCommand",
                    'exit' => $status_code,
                ])
            );
        $this->environment->expects($this->once())
            ->method('sftpConnectionInfo')
            ->willReturn($sftp_info);

        $out = $this->command->dummyCommand("$site_name.env", $options);
        $this->assertNull($out);
    }
}
