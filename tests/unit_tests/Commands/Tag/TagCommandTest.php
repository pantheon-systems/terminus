<?php

namespace Pantheon\Terminus\UnitTests\Commands\Tag;

use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\Tags;

/**
 * Class TagCommandTest
 * Abstract testing class for Pantheon\Terminus\Commands\Tag\*Command
 * @package Pantheon\Terminus\UnitTests\Commands\Tag
 */
abstract class TagCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->id = 'site_id';

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->id = 'org_id';
        $this->org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->tags = $this->tags;
        $this->org_site_membership->method('getSite')->willReturn($this->site);

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getOrgMemberships')
            ->with()
            ->willReturn($user_org_memberships);
        $user_org_memberships->expects($this->once())
            ->method('get')
            ->with($this->organization->id)
            ->willReturn($user_org_membership);
        $user_org_membership->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($this->organization);
        $this->organization->expects($this->once())
            ->method('getSiteMemberships')
            ->with()
            ->willReturn($this->org_site_memberships);
        $this->org_site_memberships->expects($this->once())
            ->method('get')
            ->with($this->site->id)
            ->willReturn($this->org_site_membership);
    }
}
