<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\Info\LogsCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\LogsCommand
 */
class LogsCommandTest extends WorkflowCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new LogsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:logs command with latest-with-logs=true.
     */
    public function testLatestLogsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $this->site->workflows->expects($this->once())
            ->method('findLatestWithLogs')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation, $this->operation]);

        $out = $this->command->logs('mysite', ['latest-with-logs' => true, 'workflow-id' => null]);
        $this->assertEquals($out, $this->expected_logs);
    }

    /**
     * Tests the workflow:info:logs command with workflow id.
     */
    public function testWorkflowIDLogsCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('add')
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation, $this->operation]);

        $out = $this->command->logs('mysite', ['latest-with-logs' => false, 'workflow-id' => '12345']);
        $this->assertEquals($out, $this->expected_logs);
    }
}
