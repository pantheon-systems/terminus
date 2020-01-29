<?php

namespace Pantheon\Terminus\UnitTests\Commands\Solr;

use Pantheon\Terminus\Commands\Solr\EnableCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Models\Solr;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class EnableCommandTest
 * Testing class for Pantheon\Terminus\Commands\Solr\EnableCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Solr
 */
class EnableCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * Tests the solr:enable command
     */
    public function testEnableSolr()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->solr = $this->getMockBuilder(Solr::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->solr->expects($this->once())
            ->method('enable');
        $this->site->expects($this->once())
            ->method('converge')
            ->willReturn($workflow);
        $this->site->method('getSolr')->willReturn($this->solr);

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

        $this->command = new EnableCommand();
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
        $this->command->enable('mysite');
    }
}
