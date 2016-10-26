<?php

namespace Pantheon\Terminus\UnitTests\Commands\Solr;

use Pantheon\Terminus\Commands\Solr\EnableCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Solr;

class EnableCommandTest extends CommandTestCase
{
    public function testDisable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->site->solr = $this->getMockBuilder(Solr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->solr->expects($this->once())
            ->method('enable');
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Solr enabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $command = new EnableCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->enable('mysite');
    }
}
