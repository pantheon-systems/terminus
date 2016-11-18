<?php

namespace Pantheon\Terminus\UnitTests\Commands\Solr;

use Pantheon\Terminus\Commands\Solr\DisableCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Solr;

/**
 * Class DisableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Solr\DisableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Solr
 */
class DisableCommandTest extends CommandTestCase
{
    /**
     * Tests the solr:disable command
     */
    public function testDisable()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->solr = $this->getMockBuilder(Solr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->solr->expects($this->once())
            ->method('disable');
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);
        $this->site->method('getSolr')->willReturn($this->solr);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Solr disabled. Converging bindings.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $command = new DisableCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->disable('mysite');
    }
}
