<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\Info\StatusCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\StatusCommand
 */
class StatusCommandTest extends WorkflowCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new StatusCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:status command with latest-with-logs=true.
     */
    public function testLatestStatusCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $this->site->workflows->expects($this->once())
            ->method('findLatestWithLogs')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'details' => 'test']);

        $out = $this->command->status('mysite', ['latest-with-logs' => true, 'workflow-id' => null]);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }

    /**
     * Tests the workflow:info:status command with workflow id.
     */
    public function testWorkflowIDStatusCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('add')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'details' => 'test']);

        $out = $this->command->status('mysite', ['latest-with-logs' => false, 'workflow-id' => '12345']);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }
}
