<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand;

/**
 * Class OperationsCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
class OperationsCommandTest extends WorkflowCommandTest
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

        $this->command = new OperationsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:operations command with the latest workflow
     */
    public function testLatestOperationsCommand()
    {
        $site_name = 'site_name';

        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation,]);
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

        $out = $this->command->operations($site_name);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }

    /**
     * Tests the workflow:info:operations command with workflow ID
     */
    public function testWorkflowIDOperationsCommand()
    {
        $site_name = 'site_name';
        $workflow_id = 'workflow id';

        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo($workflow_id))
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([$this->operation,]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->operations($site_name, ['id' => $workflow_id,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
    }

    /**
     * Tests the workflow:info:operations command when the workflow does not contain any operations
     */
    public function testWorkflowNoOperations()
    {
        $site_name = 'site_name';

        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->willReturn([]);
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

        $out = $this->command->operations($site_name);
        $this->assertNull($out);
    }
}
