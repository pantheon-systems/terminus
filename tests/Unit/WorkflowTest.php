<?php

namespace Pantheon\Terminus\Tests\Unit;

use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\Models\Workflow;
use PHPUnit\Framework\TestCase;

class WorkflowTest extends TestCase
{
    private function workflow(array $attributes): Workflow
    {
        $operations = $this->createMock(WorkflowOperations::class);
        $operations->method('serialize')->willReturn([]);

        $workflow = $this->getMockBuilder(Workflow::class)
            ->setConstructorArgs([(object)($attributes + ['id' => 'wf-1', 'description' => 'Test'])])
            ->onlyMethods(['getOperations'])
            ->getMock();
        $workflow->method('getOperations')->willReturn($operations);

        return $workflow;
    }

    public function testUnfinishedWorkflowOlderThan24HoursIsStalled()
    {
        $workflow = $this->workflow(['created_at' => time() - 25 * 3600]);

        $this->assertTrue($workflow->isStalled());
        $this->assertSame('stalled', $workflow->serialize()['status']);
    }

    public function testRecentUnfinishedWorkflowIsRunning()
    {
        $workflow = $this->workflow(['created_at' => time() - 23 * 3600]);

        $this->assertFalse($workflow->isStalled());
        $this->assertSame('running', $workflow->serialize()['status']);
    }

    public function testFinishedWorkflowIsNeverStalled()
    {
        $workflow = $this->workflow(['created_at' => time() - 48 * 3600, 'result' => 'succeeded']);

        $this->assertFalse($workflow->isStalled());
        $this->assertSame('succeeded', $workflow->serialize()['status']);
    }

    public function testGetStatusIsUnchangedForStalledWorkflow()
    {
        $workflow = $this->workflow(['created_at' => time() - 48 * 3600]);

        $this->assertSame('running', $workflow->getStatus());
    }

    public function testNullResultIsTreatedAsUnfinished()
    {
        $workflow = $this->workflow(['created_at' => time() - 25 * 3600, 'result' => null]);

        $this->assertTrue($workflow->isStalled());
    }

    public function testMissingCreatedAtIsNotStalled()
    {
        $workflow = $this->workflow([]);

        $this->assertFalse($workflow->isStalled());
        $this->assertSame('running', $workflow->serialize()['status']);
    }

    public function testWorkflowJustPastThresholdIsStalled()
    {
        $workflow = $this->workflow(['created_at' => time() - (24 * 3600 + 5)]);

        $this->assertTrue($workflow->isStalled());
    }
}
