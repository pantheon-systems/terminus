<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

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
     * Tests the workflow:info:operations command with the latest workflow
     */
    public function testLatestOperationsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->site->workflows);

        $this->site->workflows->expects($this->once())
            ->method('all')
            ->willReturn([$this->workflow,]);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation]);

        $out = $this->command->operations('mysite', ['id' => null,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }

    /**
     * Tests the workflow:info:operations command with workflow id.
     */
    public function testWorkflowIDOperationsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->site->workflows);
        $this->site->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo('12345'))
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation]);

        $out = $this->command->operations('mysite', ['id' => '12345',]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }
}
