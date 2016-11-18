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
        $this->command = new LogsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:logs command with the latest workflow
     */
    public function testLatestLogsCommand()
    {
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->workflows);

        $this->workflows->expects($this->once())
            ->method('all')
            ->willReturn([$this->workflow,]);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation, $this->operation]);

        $out = $this->command->logs('mysite', ['id' => null,]);
        $this->assertEquals($out, $this->expected_logs);
    }

    /**
     * Tests the workflow:info:logs command with workflow ID
     */
    public function testWorkflowIDLogsCommand()
    {
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo('12345'))
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation, $this->operation]);

        $out = $this->command->logs('mysite', ['id' => '12345',]);
        $this->assertEquals($out, $this->expected_logs);
    }
}
