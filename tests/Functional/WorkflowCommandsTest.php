<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class WorkflowCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class WorkflowCommandsTest extends TestCase
{

    use LoginHelperTrait;
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\ListCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\StatusCommand
     *
     * @group workflow
     * @group short
     */
    public function testWorkflowListAndStatusCommand()
    {
        $workflowsList = $this->terminusJsonResponse(sprintf('workflow:list %s', $this->getSiteName()));
        $this->assertIsArray($workflowsList);
        $this->assertNotEmpty($workflowsList);

        $workflowUuid = array_key_first($workflowsList);
        $workflow = array_shift($workflowsList);
        $fields = [
            'id',
            'env',
            'workflow',
            'user',
            'status',
            'started_at',
            'finished_at',
            'time',
        ];
        foreach ($fields as $field) {
            $this->assertArrayHasKey(
                $field,
                $workflow,
                sprintf('Workflow record should contain "%s" field. %s', $field, print_r($workflow, true))
            );
        }

        $workflowStatus = $this->terminusJsonResponse(
            sprintf('workflow:info:status %s --id=%s', $this->getSiteName(), $workflowUuid)
        );
        unset($workflowStatus['time']);
        unset($workflow['time']);
        $this->assertEquals($workflowStatus, $workflow);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Workflow\WatchCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand
     * @covers \Pantheon\Terminus\Commands\Workflow\Info\InfoBaseCommand
     *
     * @group workflow
     * @group todo
     */
    public function testWorkflowCommands()
    {
        $this->fail('To Be Written.');
    }
}
