<?php

namespace Pantheon\Terminus\UnitTests\Commands\NewRelic;

use Pantheon\Terminus\Commands\NewRelic\EnableCommand;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class EnableCommandTest
 * Testing class for Pantheon\Terminus\Commands\NewRelic\EnableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\NewRelic
 */
class EnableCommandTest extends NewRelicCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new EnableCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    public function testEnable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn('successful workflow');

        $this->new_relic->expects($this->once())
            ->method('enable')
            ->with();
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('New Relic enabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $out = $this->command->enable('mysite');
        $this->assertNull($out);
    }
}
