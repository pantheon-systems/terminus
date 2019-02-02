<?php

namespace Pantheon\Terminus\UnitTests\Commands\ServiceLevel;

use Pantheon\Terminus\Commands\ServiceLevel\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetCommandTest
 * Testing class for Pantheon\Terminus\Commands\ServiceLevel\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\ServiceLevel
 */
class SetCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * Tests the service-level:set command
     */
    public function testServiceLevelSet()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Setting plan of "{site_id}" to "{level}".'),
                $this->equalTo(['site_id' => 'my-site', 'level' => 'free'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->site->expects($this->once())
            ->method('updateServiceLevel')
            ->with('free')
            ->willReturn($workflow);

        $this->command = new SetCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setContainer($this->getContainer());
        $this->expectWorkflowProcessing();
        $this->command->set('my-site', 'free');
    }
}
