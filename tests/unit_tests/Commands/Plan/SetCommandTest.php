<?php

namespace Pantheon\Terminus\UnitTests\Commands\Plan;

use Pantheon\Terminus\Collections\Plans;
use Pantheon\Terminus\Commands\Plan\SetCommand;
use Pantheon\Terminus\Models\Plan;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetCommandTest
 * Testing class for Pantheon\Terminus\Commands\Plan\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Plan
 */
class SetCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new SetCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setContainer($this->getContainer());
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the plan:set command
     */
    public function testSet()
    {
        $plan = $this->getMockBuilder(Plan::class)
            ->disableOriginalConstructor()
            ->getMock();
        $plans = $this->getMockBuilder(Plans::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site_name = 'site_name';
        $plan->id = 'plan_id';
        $message = 'successful workflow';

        $this->site->expects($this->once())
            ->method('getPlans')
            ->with()
            ->willReturn($plans);
        $plans->expects($this->once())
            ->method('get')
            ->with($plan->id)
            ->willReturn($plan);
        $plans->expects($this->once())
            ->method('set')
            ->with($plan)
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Setting plan of "{site_id}" to "{plan_id}".')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo($message)
            );

        $out = $this->command->set($site_name, $plan->id);
        $this->assertNull($out);
    }
}
