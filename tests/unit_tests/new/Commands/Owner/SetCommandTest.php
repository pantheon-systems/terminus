<?php

namespace Pantheon\Terminus\UnitTests\Commands\Owner;

use Pantheon\Terminus\Commands\Owner\SetCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Collections\SiteUserMemberships;
use Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\Workflow;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Owner\SetCommand
 */
class SetCommandTest extends CommandTestCase
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
        $this->site->user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()->getMock();
        $this->user_membership = $this->getMockBuilder(SiteUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
   
        $this->site->user_memberships->method('get')
            ->willReturn($this->user_membership);

        $this->site->expects($this->once())->method('setOwner')->willReturn($workflow);

        $workflow->expects($this->once())->method('wait')->willReturn(true);

        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Promoted new owner')
            );
        $this->command->setOwner('dummy-site', 'a-valid-email');
    }
}
