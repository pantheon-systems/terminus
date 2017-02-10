<?php

namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\CloneContentCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class CloneCommandTesto
 * Testing class for Pantheon\Terminus\Commands\Env\CloneCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class CloneCommandTest extends EnvCommandTest
{
    protected $command;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CloneContentCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    public function testCloneFiles()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->once())
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
        $this->workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning files from {from_name} environment to {target_env} environment'),
                $this->equalTo(['from_name' => $this->environment->id, 'target_env' => $target_env,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['files-only' => true,]);
    }

    /**
     * Tests CloneContentCommand::cloneContent when declining the confirmation
     *
     * @todo Remove this when removing TerminusCommand::confirm()
     */
    public function testCloneFilesConfirmationDecline()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->expectConfirmation(false);
        $this->environment->expects($this->never())
            ->method('cloneFiles');
        $this->workflow->expects($this->never())
            ->method('checkProgress');
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->never())
            ->method('log');

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
        $this->environment->expects($this->once())
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
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning database from {from_name} environment to {target_env} environment'),
                $this->equalTo(['from_name' => $this->environment->id, 'target_env' => $target_env,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['db-only' => true,]);
    }

    public function testCloneAll()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->environment->expects($this->once())
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
        $worlflow1->expects($this->once())->method('checkProgress')->willReturn(true);
        $worlflow1->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $worlflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->expects($this->once())
            ->method('cloneDatabase')
            ->willReturn($worlflow2);
        $worlflow2->expects($this->once())->method('checkProgress')->willReturn(true);
        $worlflow2->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning files from {from_name} environment to {target_env} environment'),
                $this->equalTo(['from_name' => $this->environment->id, 'target_env' => $target_env,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );
        $this->logger->expects($this->at(2))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Cloning database from {from_name} environment to {target_env} environment'),
                $this->equalTo(['from_name' => $this->environment->id, 'target_env' => $target_env,])
            );
        $this->logger->expects($this->at(3))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env);
    }

    /**
     * Tests env:clone command when attempting to clone from an uninitialized environment
     */
    public function testCloneFilesFromUninitialized()
    {
        $site_name = 'site-name';
        $this->environment->id = 'dev';
        $target_env = 'test';

        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn($this->environment->id);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->environment->expects($this->once())
            ->method('isInitialized')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->never())
            ->method('cloneFiles');
        $this->workflow->expects($this->never())
            ->method('checkProgress');
        $this->workflow->expects($this->never())
            ->method('getMessage');
        $this->logger->expects($this->never(0))
            ->method('log');

        $this->setExpectedException(
            TerminusException::class,
            "$site_name's {$this->environment->id} environment cannot be cloned because it has not been initialized. Please run `env:deploy $site_name.{$this->environment->id}` to initialize it."
        );

        $this->command->cloneContent("$site_name.{$this->environment->id}", $target_env, ['files-only' => true,]);
    }

    public function testCloneNone()
    {
        $this->setExpectedException(TerminusException::class, 'You cannot specify both --db-only and --files-only');
        $this->command->cloneContent('mysite.dev', 'test', ['db-only' => true, 'files-only' => true,]);
    }
}
