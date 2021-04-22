<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand;

/**
 * Class OperationsCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\OperationsCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
class OperationsCommandTest extends InfoCommandTest
{
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

        $this->site_name = 'Site Name';

        $this->workflow->expects($this->once())
            ->method('getOperations')
            ->with()
            ->willReturn($this->operations);

        $this->command = new OperationsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:operations command with the latest workflow
     */
    public function testLatestOperationsCommand()
    {
        $op_data = [
            ['id' => '12345', 'log_output' => 'The mock operation log output.', 'description' => 'Mock operation'],
            ['id' => '67890', 'log_output' => 'The other mock op log output.', 'description' => 'Mock operation 2'],
        ];

        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->operations->expects($this->any())
            ->method('serialize')
            ->willReturn($op_data);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Showing latest workflow on {site}.'),
                $this->equalTo(['site' => $this->site_name,])
            );

        $out = $this->command->operations($this->site_name);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($op_data, $out->getArrayCopy());
    }

    /**
     * Tests the workflow:info:operations command with workflow ID
     */
    public function testWorkflowIDOperationsCommand()
    {
        $workflow_id = 'workflow id';
        $op_data = [
            ['id' => '12345', 'log_output' => 'The mock operation log output.', 'description' => 'Mock operation'],
            ['id' => '67890', 'log_output' => 'The other mock op log output.', 'description' => 'Mock operation 2'],
        ];

        $this->site->expects($this->never())
            ->method('getName');
        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo($workflow_id))
            ->willReturn($this->workflow);
        $this->operations->expects($this->any())
            ->method('serialize')
            ->willReturn($op_data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->operations($this->site_name, ['id' => $workflow_id,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($op_data, $out->getArrayCopy());
    }

    /**
     * Tests the workflow:info:operations command when the workflow does not contain any operations
     */
    public function testWorkflowNoOperations()
    {
        $op_data = [];

        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->workflows->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('getOperations')
            ->willReturn($this->operations);
        $this->operations->expects($this->any())
            ->method('serialize')
            ->willReturn($op_data);
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

        $out = $this->command->operations($this->site_name);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEmpty($out->getArrayCopy());
    }
}
