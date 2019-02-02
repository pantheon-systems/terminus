<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CloneContentCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class CloneContentCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\CloneContentCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class CloneContentCommandTest extends EnvCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CloneContentCommand();
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
    }

    public function testCloneFiles()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->exactly(2))
            ->method('isInitialized')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('cloneFiles')
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning files from {source} environment to {target} environment'),
                $this->equalTo(['source' => $this->environment->id, 'target' => $this->environment->id,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['files-only' => true,]);
    }

    public function testCloneDatabase()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->exactly(2))
            ->method('isInitialized')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('cloneDatabase')
            ->with($this->environment, ['clear_cache' => false, 'updatedb' => false,])
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning database from {source} environment to {target} environment'),
                $this->equalTo(['source' => $this->environment->id, 'target' => $this->environment->id,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['cc' => false, 'db-only' => true, 'updatedb' => false,]);
    }

    public function testCloneAll()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->exactly(2))
            ->method('isInitialized')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->expectConfirmation();

        $worlflow1 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->expects($this->once())
            ->method('cloneFiles')
            ->willReturn($worlflow1);
        $worlflow1->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $worlflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->expects($this->once())
            ->method('cloneDatabase')
            ->with($this->environment, ['clear_cache' => false, 'updatedb' => false,])
            ->willReturn($worlflow2);
        $worlflow2->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning files from {source} environment to {target} environment'),
                $this->equalTo(['source' => $this->environment->id, 'target' => $this->environment->id,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );
        $this->logger->expects($this->at(2))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning database from {source} environment to {target} environment'),
                $this->equalTo(['source' => $this->environment->id, 'target' => $this->environment->id,])
            );
        $this->logger->expects($this->at(3))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env);
    }

    /**
     * Tests env:clone command when attempting to clone to an uninitialized environment
     */
    public function testCloneFilesToUninitialized()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->at(0))
            ->method('isInitialized')
            ->with()
            ->willReturn(true);
        $this->environment->expects($this->at(1))
            ->method('isInitialized')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->never())
            ->method('cloneFiles');
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->never(0))
            ->method('log');

        $this->environment->method('getName')->willReturn($this->environment->id);
        $this->environment->method('getSite')->willReturn($this->site);
        $this->site->method('getName')->willReturn($site_name);

        $this->setExpectedException(
            TerminusException::class,
            "$site_name's {$this->environment->id} environment cannot be cloned into because it has not been initialized. Please run `env:deploy $site_name.{$this->environment->id}` to initialize it."
        );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['files-only' => true,]);
    }

    /**
     * Tests env:clone command when attempting to clone from an uninitialized environment
     */
    public function testCloneFilesFromUninitialized()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->never())
            ->method('cloneFiles');
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->never(0))
            ->method('log');

        $this->environment->method('getName')->willReturn($this->environment->id);
        $this->environment->method('getSite')->willReturn($this->site);
        $this->site->method('getName')->willReturn($site_name);

        $this->setExpectedException(
            TerminusException::class,
            "$site_name's {$this->environment->id} environment cannot be cloned from because it has not been initialized. Please run `env:deploy $site_name.{$this->environment->id}` to initialize it."
        );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['files-only' => true,]);
    }

    public function testCloneNone()
    {
        $this->setExpectedException(TerminusException::class, 'You cannot specify both --db-only and --files-only');
        $this->command->cloneContent('mysite.dev', 'test', ['db-only' => true, 'files-only' => true,]);
    }
}
