<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Pantheon\Terminus\Models\WorkflowOperation;
use Pantheon\Terminus\Commands\Workflow\Info\LogsCommand;

/**
 * Class LogsCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\LogsCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
class LogsCommandTest extends InfoCommandTest
{
    /**
     * @var WorkflowOperation
     */
    protected $operation;
    /**
     * @var string
     */
    protected $site_name;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->operation = $this->getMockBuilder(WorkflowOperation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_name = 'Site Name';

        $this->workflow->expects($this->once())
            ->method('getOperations')
            ->with()
            ->willReturn($this->operations);

        $this->command = new LogsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:logs command with the latest workflow
     */
    public function testLatestLogsCommand()
    {
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->operations->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->operation,]);
        $this->operation->expects($this->at(0))
            ->method('get')
            ->with('log_output')
            ->willReturn(true);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $this->site_name,])
            );
        $this->operation->expects($this->at(1))
            ->method('__toString')
            ->with()
            ->willReturn($this->expected_logs);

        $out = $this->command->logs($this->site_name);
        $this->assertEquals($this->expected_logs, $out);
    }

    /**
     * Tests the workflow:info:logs command with workflow ID
     */
    public function testWorkflowIDLogsCommand()
    {
        $this->workflow->id = '12345';

        $this->site->expects($this->never())
            ->method('getName');
        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo($this->workflow->id))
            ->willReturn($this->workflow);
        $this->operations->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->operation,]);
        $this->operation->expects($this->at(0))
            ->method('get')
            ->with('log_output')
            ->willReturn(true);
        $this->operation->expects($this->at(1))
            ->method('__toString')
            ->with()
            ->willReturn($this->expected_logs);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->logs($this->site_name, ['id' => $this->workflow->id,]);
        $this->assertEquals($this->expected_logs, $out);
    }

    /**
     * Tests the workflow:info:logs command when the workflow has no operations
     */
    public function testLatestNoOperations()
    {
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->operations->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([]);
        $this->operation->expects($this->never())
            ->method('has');
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $this->site_name,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Workflow does not contain any operations.')
            );

        $out = $this->command->logs($this->site_name);
        $this->assertEmpty($out);
    }

    /**
     * Tests the workflow:info:logs command when the workflow operations have no logs
     */
    public function testLatestNoLogs()
    {
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->operations->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->operation,]);
        $this->operation->expects($this->at(0))
            ->method('get')
            ->with('log_output')
            ->willReturn(false);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Workflow operations did not contain any logs.')
            );

        $out = $this->command->logs($this->site_name);
        $this->assertEmpty($out);
    }
}
