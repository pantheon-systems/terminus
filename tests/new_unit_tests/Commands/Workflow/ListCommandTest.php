<?php
namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\UnitTests\Commands\Workflow\WorkflowCommandTest;
use Pantheon\Terminus\Commands\Workflow\ListCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Workflow\ListCommand
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
        $this->command->setSession($this->session);
    }

    /**
     * Tests the workflow:list command.
     */
    public function testListCommand()
    {
        $this->site->workflows->expects($this->once())
            ->method('fetch')
            ->willReturn(null);

        $this->site->workflows->expects($this->once())
            ->method('all')
            ->willReturn([$this->workflow]);

        $this->workflow->expects($this->once())
            ->method('serialize')
            ->willReturn(['id' => '12345', 'details' => 'test']);

        $out = $this->command->wfList('mysite');
        foreach ($out as $w) {
            $this->assertEquals($w['id'], '12345');
            $this->assertEquals($w['details'], 'test');
        }
    }
}
