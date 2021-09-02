<?php

namespace Pantheon\Terminus\UnitTests\HTTPS;

use Pantheon\Terminus\Commands\HTTPS\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class DeleteCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\HTTPS\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\HTTPS
 */
class RemoveCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->command = new RemoveCommand();
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
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
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('disableHttpsCertificate')
            ->willReturn($workflow);


        $this->logger->expects($this->once())
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

        $this->expectException(TerminusException::class);
        $this->command->remove('mysite.dev');
    }
}
