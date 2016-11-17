<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Collections\SiteUserMemberships;
use Terminus\Models\Workflow;
use Terminus\Models\SiteUserMembership;

/**
 * Base testing class for Pantheon\Terminus\Commands\Site\Team
 */
abstract class TeamCommandTest extends CommandTestCase
{
    protected $team;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user_membership = $this->getMockBuilder(SiteUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->user_memberships->method('get')
            ->willReturn($this->user_membership);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
