<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

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
     * Tests the workflow:info:status command with the latest workflow
     */
    public function testLatestStatusCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->site->workflows);

        $this->site->workflows->expects($this->once())
            ->method('all')
            ->willReturn([$this->workflow,]);

        $this->workflow->expects($this->once())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'details' => 'test']);

        $out = $this->command->status('mysite', ['id' => null,]);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }

    /**
     * Tests the workflow:info:status command with workflow ID
     */
    public function testWorkflowIDStatusCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->site->workflows);
        $this->site->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo('12345'))
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'details' => 'test']);

        $out = $this->command->status('mysite', ['id' => '12345',]);
        $this->assertInstanceOf(AssociativeList::class, $out);
    }
}
