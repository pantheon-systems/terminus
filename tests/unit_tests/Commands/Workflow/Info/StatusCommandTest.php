<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow\Info;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Workflow\Info\StatusCommand;

/**
 * Class StatusCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\Info\StatusCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow\Info
 */
class StatusCommandTest extends InfoCommandTest
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->data = ['id' => '12345', 'details' => 'test',];

        $this->command = new StatusCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:info:status command with the latest workflow
     */
    public function testLatestStatusCommand()
    {
        $this->workflows->expects($this->once())
            ->method('all')
            ->willReturn([$this->workflow,]);
        $this->workflow->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);

        $out = $this->command->status('mysite', ['id' => null,]);
        $this->assertInstanceOf(PropertyList::class, $out);
    }

    /**
     * Tests the workflow:info:status command with workflow ID
     */
    public function testWorkflowIDStatusCommand()
    {
        $this->workflows->expects($this->once())
            ->method('get')
            ->with($this->equalTo('12345'))
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);

        $out = $this->command->status('mysite', ['id' => '12345',]);
        $this->assertInstanceOf(PropertyList::class, $out);
    }
}
