<?php

namespace Pantheon\Terminus\UnitTests\Commands\Connection;

use Pantheon\Terminus\Commands\Connection\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SetCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Connection\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Connection
 */
class SetCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->command = new SetCommand($this->getConfig());

        // use the basic mocks from CommandTestCase
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises connection:set git mode
     */
    public function testConnectionSetSuccess()
    {
        // dummy workflow instance
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())->method('changeConnectionMode')
            ->with($this->equalTo('a-valid-mode'))->willReturn($workflow);

        // should display a notice about the mode switch
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', 'a-valid-mode');
    }

    /**
     * Exercises connection:set git mode
     */
    public function testConnectionSetNoOp()
    {
        $this->environment->expects($this->once())->method('changeConnectionMode')
            ->with($this->equalTo('the-current-mode'))->willReturn('noop');

        // should display a notice about the mode switch
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('noop')
            );

        // trigger command call expectations
        $this->command->connectionSet('dummy-site.dummy-env', 'the-current-mode');
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
