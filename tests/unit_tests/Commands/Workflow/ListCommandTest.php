<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\Commands\Workflow\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow
 */
class ListCommandTest extends WorkflowCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the workflow:list command.
     */
    public function testListCommand()
    {
        $this->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn($this->workflows);

        $this->workflows->expects($this->once())
            ->method('serialize')
            ->willReturn(['12345' => ['id' => '12345', 'details' => 'test']]);


        $out = $this->command->wfList('mysite');
        foreach ($out as $w) {
            $this->assertEquals($w['id'], '12345');
            $this->assertEquals($w['details'], 'test');
        }
    }
}
