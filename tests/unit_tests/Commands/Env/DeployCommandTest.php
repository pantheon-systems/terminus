<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\DeployCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class DeployCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\DeployCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class DeployCommandTest extends EnvCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new DeployCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the env:deploy command success with all parameters
     */
    public function testDeploy()
    {
        $this->environment->id = 'test';

        $this->environment->expects($this->exactly(2))
            ->method('isInitialized')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('deploy')
            ->willReturn($this->workflow)
            ->with([
                'updatedb' => 0,
                'clear_cache' => 0,
                'annotation' => 'Deploy from Terminus',
                'clone_database' => [
                    'from_environment' => 'live'
                ],
                'clone_files' => [
                    'from_environment' => 'live'
                ]
            ]);

        // Run the deploy.
        $this->command->deploy(
            "mysite.{$this->environment->id}",
            ['sync-content' => true, 'note' => 'Deploy from Terminus', 'cc' => false, 'updatedb' => false,]
        );
    }

    /**
     * Tests the env:deploy command where no code is deployable
     */
    public function testDeployNoCode()
    {
        $this->environment->id = 'test';

        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(false);
        $this->environment->expects($this->never())
            ->method('deploy');
        $this->logger->expects($this->once())
            ->method('log');

        // Run the deploy.
        $this->command->deploy("mysite.{$this->environment->id}");
    }

    /**
     * Tests the env:deploy command to live
     */
    public function testDeployLive()
    {
        $this->environment->id = 'live';

        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('deploy')
            ->willReturn($this->workflow)
            ->with([
                'updatedb' => 1,
                'clear_cache' => 1,
                'annotation' => 'Deploy from Terminus',
            ]);

        // Run the deploy.
        $this->command->deploy(
            "mysite.{$this->environment->id}",
            ['sync-content' => true, 'note' => 'Deploy from Terminus', 'cc' => true, 'updatedb' => true,]
        );
    }

    /**
     * Tests the env:deploy command when the environment is uninitialized and a deploy message is supplied
     */
    public function testDeployUninitializedWithMessage()
    {
        $this->environment->id = 'uninitialized';
        $note = 'Never running from a real fight';

        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false);
        $this->environment->expects($this->once())
            ->method('initializeBindings')
            ->with(['annotation' => $note,])
            ->willReturn($this->workflow);

        // Run the deploy.
        $this->command->deploy("mysite.{$this->environment->id}", compact('note'));
    }

    /**
     * Tests the env:deploy command when the environment is uninitialized and no deploy message is given
     */
    public function testDeployUninitializedWithoutMessage()
    {
        $this->environment->id = 'uninitialized';

        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false);
        $this->environment->expects($this->once())
            ->method('initializeBindings')
            ->with()
            ->willReturn($this->workflow);

        // Run the deploy.
        $this->command->deploy("mysite.{$this->environment->id}");
    }

    /**
     * Tests the env:deploy command when trying to sync from an uninitialized environment
     */
    public function testDeploySyncFromUninitialized()
    {
        $this->environment->id = 'test';
        $site_name = 'site name';

        $this->environment->expects($this->at(0))
            ->method('isInitialized')
            ->willReturn(true);
        $this->environment->expects($this->once())
            ->method('hasDeployableCode')
            ->willReturn(true);
        $this->environment->expects($this->at(1))
            ->method('isInitialized')
            ->willReturn(false);
        $this->site->expects($this->once())
            ->method('getName')
            ->willReturn($site_name);
        $this->environment->expects($this->never())
            ->method('deploy');
        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(false);

        $this->setExpectedException(
            TerminusException::class,
            "$site_name's live environment cannot be cloned because it has not been initialized."
        );

        // Run the deploy.
        $this->command->deploy(
            "$site_name.{$this->environment->id}",
            ['sync-content' => true, 'note' => 'Deploy from Terminus', 'cc' => false, 'updatedb' => false,]
        );
    }
}
