<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\SiteUserMembership;

/**
 * Base testing class for Pantheon\Terminus\Commands\Site\Team
 */
abstract class TeamCommandTest extends CommandTestCase
{
    protected $team;
    protected $user_memberships;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->method('getUserMemberships')->willReturn($this->user_memberships);

        $this->user_membership = $this->getMockBuilder(SiteUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user_memberships->method('get')
            ->willReturn($this->user_membership);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
