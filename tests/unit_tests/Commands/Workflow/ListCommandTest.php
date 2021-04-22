<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Workflow\ListCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow
 */
class ListCommandTest extends WorkflowCommandTest
{
    /**
     * @var string
     */
    protected $site_name;
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->site_name = 'site_name';

        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->workflows->expects($this->once())
            ->method('getCollectedClass')
            ->with()
            ->willReturn(Workflow::class);

        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($this->site_name);
    }

    /**
     * Tests the workflow:list command
     */
    public function testListCommand()
    {
        $this->workflows->expects($this->once())
            ->method('setPaging')
            ->with(false)
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('serialize')
            ->willReturn(['12345' => ['id' => '12345', 'details' => 'test',],]);

        $out = $this->command->wfList($this->site_name);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        foreach ($out as $w) {
            $this->assertEquals($w['id'], '12345');
            $this->assertEquals($w['details'], 'test');
        }
    }

    /**
     * Tests the workflow:list command when no workflows have been run
     */
    public function testListCommandEmpty()
    {
        $this->workflows->expects($this->once())
            ->method('setPaging')
            ->with(false)
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('warning'),
                $this->equalTo('No workflows have been run on {site}.'),
                $this->equalTo(['site' => $this->site_name,])
            );

        $out = $this->command->wfList($this->site_name);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([], $out->getArrayCopy());
    }
}
