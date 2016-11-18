<?php

namespace Pantheon\Terminus\UnitTests\HTTPS;

use Pantheon\Terminus\Commands\HTTPS\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class DeleteCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\HTTPS\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\HTTPS
 */
class RemoveCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the https:remove command
     */
    public function testRemove()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('disableHttpsCertificate');
        $this->environment->expects($this->once())
            ->method('convergeBindings')
            ->willReturn($workflow);


        // should display a notice about the mode switch
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('HTTPS has been disabled and the environment\'s bindings will now be converged.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->command->remove('mysite.dev');
    }

    /**
     * Tests the https:remove command when it fails
     */
    public function testRemoveFailed()
    {
        $this->environment->expects($this->once())
            ->method('disableHttpsCertificate')
            ->will($this->throwException(new TerminusException('Could not delete')));

        $this->setExpectedException(TerminusException::class);
        $this->command->remove('mysite.dev');
    }
}
