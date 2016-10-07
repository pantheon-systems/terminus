<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand
 */
class OperationsCommandTest extends WorkflowCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new OperationsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:operations command with latest-with-logs=true.
     */
    public function testLatestOperationsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $this->site->workflows->expects($this->once())
            ->method('findLatestWithLogs')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation]);

        $out = $this->command->operations('mysite', ['latest-with-logs' => true, 'workflow-id' => null]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }

    /**
     * Tests the workflow:info:operations command with workflow id.
     */
    public function testWorkflowIDOperationsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('add')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation]);

        $out = $this->command->operations('mysite', ['latest-with-logs' => false, 'workflow-id' => '12345']);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }
}
