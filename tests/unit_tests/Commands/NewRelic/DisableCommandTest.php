<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Pantheon\Terminus\Commands\NewRelic\DisableCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class DisableCommandTest
 * Testing class for Pantheon\Terminus\Commands\NewRelic\DisableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\NewRelic
 */
class DisableCommandTest extends NewRelicCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new DisableCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the new-relic:disable command
     */
    public function testDisable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn('successful workflow');

        $this->new_relic->expects($this->once())
            ->method('disable')
            ->with();
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('New Relic disabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $out = $this->command->disable('mysite');
        $this->assertNull($out);
    }
}
