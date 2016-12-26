<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\Info\LogsCommand;

/**
 * Class LogsCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\LogsCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
class LogsCommandTest extends WorkflowCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workflows->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->workflows);

        $this->command = new LogsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:logs command with the latest workflow
     */
    public function testLatestLogsCommand()
    {
        $site_name = 'site name';

        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->with()
            ->willReturn([$this->operation, $this->operation,]);
        $this->operation->expects($this->any())
            ->method('has')
            ->with($this->equalTo('log_output'))
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->logs($site_name);
        $this->assertEquals($out, $this->expected_logs);
    }

    /**
     * Tests the workflow:info:logs command with workflow ID
     */
    public function testWorkflowIDLogsCommand()
    {
        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo('12345'))
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->with()
            ->willReturn([$this->operation, $this->operation,]);
        $this->operation->expects($this->any())
            ->method('has')
            ->with($this->equalTo('log_output'))
            ->willReturn(true);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->logs('mysite', ['id' => '12345',]);
        $this->assertEquals($out, $this->expected_logs);
    }

    /**
     * Tests the workflow:info:logs command when the workflow has no operations
     */
    public function testLatestNoOperations()
    {
        $site_name = 'site name';

        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->with()
            ->willReturn([]);
        $this->operation->expects($this->never())
            ->method('has');
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $site_name,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Workflow does not contain any operations.')
            );

        $out = $this->command->logs($site_name);
        $this->assertNull($out);
    }

    /**
     * Tests the workflow:info:logs command when the workflow operations have no logs
     */
    public function testLatestNoLogs()
    {
        $site_name = 'site name';

        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->with()
            ->willReturn([$this->operation, $this->operation,]);
        $this->operation->expects($this->any())
            ->method('has')
            ->with($this->equalTo('log_output'))
            ->willReturn(false);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $site_name,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Workflow operations did not contain any logs.')
            );

        $out = $this->command->logs($site_name);
        $this->assertEmpty($out);
    }
}
