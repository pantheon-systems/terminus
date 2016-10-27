<?php

namespace Pantheon\Terminus\UnitTests\Commands\ServiceLevel;

use Pantheon\Terminus\Commands\ServiceLevel\SetCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class SetCommandTest extends CommandTestCase
{
    public function setServiceLevelSet()
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
                $this->equalTo('Setting service level of "my-site" to "free".')
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
        $command->set('free', 'my-site');
    }
}
