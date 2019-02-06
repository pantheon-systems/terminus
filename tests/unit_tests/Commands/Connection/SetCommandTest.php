<?php

namespace Pantheon\Terminus\UnitTests\Commands\Connection;

use Pantheon\Terminus\Commands\Connection\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Connection\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Connection
 */
class SetCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        // dummy workflow instance
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new SetCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
    }

    /**
     * Exercises connection:set git mode
     */
    public function testConnectionSetSuccess()
    {
        $message = 'successful workflow';
        $mode = 'mode';

        $this->environment->expects($this->once())
            ->method('hasUncommittedChanges')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->once())
            ->method('changeConnectionMode')
            ->with($mode)
            ->willReturn($this->workflow);
        // workflow succeeded
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->willReturn($message);
        // should display a notice about the mode switch
        $this->logger->expects($this->once())
            ->method('log')
            ->with('notice', $message);

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', $mode);
    }

    /**
     * Exercises connection:set when Environment::changeConnectionMode(mode) throws an error we're not expecting to use
     */
    public function testConnectionSetThrowsError()
    {
        $message = 'Chimken nuggers in bork soss';
        $mode = 'mode';

        $this->environment->expects($this->once())
            ->method('hasUncommittedChanges')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->once())
            ->method('changeConnectionMode')
            ->with($mode)
            ->will($this->throwException(new TerminusException($message)));
        // workflow succeeded
        $this->workflow->expects($this->never())
            ->method('getMessage');
        // should display a notice about the mode switch
        $this->logger->expects($this->never())
            ->method('log');
        $this->setExpectedException(TerminusException::class, $message);

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', $mode);
    }

    /**
     * Exercises connection:set when trying to change into the same mode as the environment is already on
     */
    public function testConnectionSetToSameMode()
    {
        $mode = 'mode';
        $message = "This environment is already set to $mode.";

        $this->environment->expects($this->once())
            ->method('hasUncommittedChanges')
            ->with()
            ->willReturn(false);
        $this->environment->expects($this->once())
            ->method('changeConnectionMode')
            ->with($mode)
            ->will($this->throwException(new TerminusException($message)));
        // workflow succeeded
        $this->workflow->expects($this->never())
            ->method('getMessage');
        // should display a notice about the mode switch
        $this->logger->expects($this->once())
            ->method('log')
            ->with('notice', $message);

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', $mode);
    }

    /**
     * Exercises connection:set command when changing from SFTP but the environment has uncommitted changes
     */
    public function testConnectionSetWithUncommittedChanges()
    {
        $message = 'successful workflow';
        $mode = 'mode';

        $this->environment->expects($this->once())
            ->method('hasUncommittedChanges')
            ->with()
            ->willReturn(true);
        // should display a notice about the mode switch
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with('warning');
        $this->expectConfirmation();
        $this->environment->expects($this->once())
            ->method('changeConnectionMode')
            ->with($mode)
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($message)
            );

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', $mode);
    }

    /**
     * Exercises connection:set with invalid test environment
     */
    public function testConnectionSetInvalidTestEnv()
    {
        $this->environment->id = 'test';

        $this->setExpectedException(
            TerminusException::class,
            'Connection mode cannot be set on the test environment'
        );

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.test', 'any-mode');
    }

    /**
     * Exercises connection:set with invalid live environment
     */
    public function testConnectionSetInvalidLiveEnv()
    {
        $this->environment->id = 'live';

        $this->setExpectedException(
            TerminusException::class,
            'Connection mode cannot be set on the live environment'
        );

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.live', 'any-mode');
    }
}
