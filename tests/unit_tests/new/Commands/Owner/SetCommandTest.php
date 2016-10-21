<?php

namespace Pantheon\Terminus\UnitTests\Commands\Owner;

use Pantheon\Terminus\Commands\Owner\SetCommand;
use Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Import\ImportCommand
 */
class ImportCommandTest extends CommandTestCase
{

    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new SetCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }
    
    /**
     * Exercises site:import command with a valid url
     *
     * @return void
     *
     */
    public function testOwnerSetValidOwner()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->once())->method('wait')->willReturn(true);

        $this->site->expects($this->once())->method('setOwner')
            ->with($this->equalTo('a-valid-uuid'))->willReturn($workflow);
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Promoted new owner')
            );
        $this->command->setOwner('dummy-site', 'a-valid-uuid');
    }
}
