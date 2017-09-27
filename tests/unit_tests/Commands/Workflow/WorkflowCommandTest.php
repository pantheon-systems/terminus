<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\Collections\WorkflowOperations;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Workflow;
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
     * @var WorkflowOperations
     */
    protected $operations;
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

        $this->expected_logs = <<<'EOT'

------ Mock operation ------
The mock operation log output.

------ Mock operation ------
The mock operation log output.

EOT;
        $this->operations = $this->getMockBuilder(WorkflowOperations::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow->method('getOperations')->willReturn($this->operations);
        $this->site->method('getWorkflows')->willReturn($this->workflows);
    }
}
