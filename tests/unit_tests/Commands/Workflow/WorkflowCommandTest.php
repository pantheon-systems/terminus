<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;
use Terminus\Models\WorkflowOperation;
use Terminus\Collections\Workflows;

/**
 * Base testing class for Pantheon\Terminus\Commands\Workflow
 */
abstract class WorkflowCommandTest extends CommandTestCase
{
    protected $workflow;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation = $this->getMockBuilder(WorkflowOperation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->operation->expects($this->any())
            ->method('has')
            ->with('log_output')
            ->willReturn(true);

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
