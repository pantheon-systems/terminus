<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\WorkflowOperation;
use Pantheon\Terminus\Collections\Workflows;

/**
 * Class WorkflowCommandTest
 * Base testing class for Pantheon\Terminus\Commands\Workflow
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow
 */
abstract class WorkflowCommandTest extends CommandTestCase
{
    /**
     * @var string
     */
    protected $expected_logs;
    /**
     * @var WorkflowOperation
     */
    protected $operation;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->method('getWorkflows')->willReturn($this->workflows);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation = $this->getMockBuilder(WorkflowOperation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation->expects($this->any())
            ->method('description')
            ->willReturn('Mock operation');

        $this->operation->expects($this->any())
            ->method('get')
            ->with('log_output')
            ->willReturn('The mock operation log output.');

        $this->operation->expects($this->any())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'log_output' => 'The mock operation log output.', 'description' => 'Mock operation']);

        $this->expected_logs = <<<'EOT'

------ Mock operation ------
The mock operation log output.

------ Mock operation ------
The mock operation log output.

EOT;
    }
}
