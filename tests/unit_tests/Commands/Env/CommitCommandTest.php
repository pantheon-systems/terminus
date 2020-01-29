<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CommitCommand;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class CommitCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\CommitCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class CommitCommandTest extends EnvCommandTest
{
    use WorkflowProgressTrait;

    /**
     * Sets up the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CommitCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->environment->id = 'dev';
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the env:commit command to success with all parameters
     */
    public function testCommit()
    {
        $message = 'Custom message.';

        $this->environment->expects($this->once())
        ->method('diffstat')
        ->willReturn(['a', 'b']);
        $this->environment->expects($this->once())
        ->method('get')
        ->with('connection_mode')
        ->willReturn('sftp');
        $this->environment->expects($this->once())
        ->method('commitChanges')
        ->with($this->equalTo($message))
        ->willReturn($this->workflow);
        $this->logger->expects($this->once())
        ->method('log')
        ->with(
            $this->equalTo('notice'),
            $this->equalTo('Your code was committed.')
        );

        $out = $this->command->commit(
            'mysite.' . $this->environment->id,
            compact('message')
        );
        $this->assertNull($out);
    }

  /**
   * Tests the env:commit command when there are no changes to be committed
   */
    public function testCommitNoChanges()
    {
        $this->environment->expects($this->once())
        ->method('diffstat')
        ->willReturn([]);
        $this->environment->expects($this->never())
        ->method('commitChanges');
        $this->logger->expects($this->once())
        ->method('log')
        ->with(
            $this->equalTo('warning'),
            $this->equalTo('There is no code to commit.')
        );

        $out = $this->command->commit('mysite.' . $this->environment->id);
        $this->assertNull($out);
    }

  /**
   * Tests the env:commit command when there are no changes to be committed
   */
    public function testCommitForce()
    {
        $message = 'Custom message.';

        $this->environment->expects($this->once())
        ->method('get')
        ->with('connection_mode')
        ->willReturn('sftp');
        $this->environment->expects($this->once())
        ->method('commitChanges')
        ->with($this->equalTo($message))
        ->willReturn($this->workflow);
        $this->logger->expects($this->once())
        ->method('log')
        ->with(
            $this->equalTo('notice'),
            $this->equalTo('Your code was committed.')
        );

        $out = $this->command->commit(
            'mysite.' . $this->environment->id,
            ['message' => $message, 'force' => true]
        );
        $this->assertNull($out);
    }

  /**
   * Tests the env:commit command when there are no changes to be committed
   */
    public function testCommitForceGitMode()
    {
        $this->environment->expects($this->once())
        ->method('get')
        ->with('connection_mode')
        ->willReturn('git');
        $this->environment->expects($this->never())
        ->method('commitChanges');
        $this->logger->expects($this->once())
        ->method('log')
        ->with(
            $this->equalTo('warning'),
            $this->equalTo('You can only commit code in an environment that is set to sftp mode.')
        );

        $out = $this->command->commit(
            'mysite.' . $this->environment->id,
            ['force' => true]
        );
        $this->assertNull($out);
    }
}
