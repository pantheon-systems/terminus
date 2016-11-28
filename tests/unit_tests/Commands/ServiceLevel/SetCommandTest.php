<?php

namespace Pantheon\Terminus\UnitTests\Commands\ServiceLevel;

use Pantheon\Terminus\Commands\ServiceLevel\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class SetCommandTest
 * Testing class for Pantheon\Terminus\Commands\ServiceLevel\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\ServiceLevel
 */
class SetCommandTest extends CommandTestCase
{
    /**
     * Tests the service-level:set command
     */
    public function testServiceLevelSet()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Setting plan of "{site_id}" to "{level}".'),
                $this->equalTo(['site_id' => 'my-site', 'level' => 'free'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $this->site->expects($this->once())
            ->method('updateServiceLevel')
            ->with('free')
            ->willReturn($workflow);

        $command = new SetCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->set('my-site', 'free');
    }
}
