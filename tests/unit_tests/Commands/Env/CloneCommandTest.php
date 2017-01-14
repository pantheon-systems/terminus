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
        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn('dev');
        $this->environment->expects($this->once())
            ->method('cloneFiles')
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo("Cloning files from {from_name} environment to {target_env} environment"),
                $this->equalTo(['from_name' => 'dev', 'target_env' => 'test'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent('mysite.dev', 'test', ['files-only' => true]);
    }

    public function testCloneDatabase()
    {
        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn('dev');
        $this->environment->expects($this->once())
            ->method('cloneDatabase')
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $this->workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo("Cloning database from {from_name} environment to {target_env} environment"),
                $this->equalTo(['from_name' => 'dev', 'target_env' => 'test'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent('mysite.dev', 'test', ['db-only' => true]);
    }

    public function testCloneAll()
    {
        $this->environment->expects($this->any())
            ->method('getName')
            ->willReturn('dev');

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
                $this->equalTo("Cloning files from {from_name} environment to {target_env} environment"),
                $this->equalTo(['from_name' => 'dev', 'target_env' => 'test'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );
        $this->logger->expects($this->at(2))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo("Cloning database from {from_name} environment to {target_env} environment"),
                $this->equalTo(['from_name' => 'dev', 'target_env' => 'test'])
            );
        $this->logger->expects($this->at(3))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->cloneContent('mysite.dev', 'test');
    }

    public function testCloneNone()
    {
        $this->setExpectedException(TerminusException::class, "You cannot specify both --db-only and --files-only");
        $this->command->cloneContent('mysite.dev', 'test', ['db-only' => true, 'files-only' => true]);
    }
}
