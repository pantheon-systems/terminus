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
     * @var string
     */
    protected $org_name;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var string
     */
    protected $site_name;
    /**
     * @var Tags
     */
    protected $tags;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->id = 'site_id';
        $this->site_name = 'site_name';
        $this->org_name = 'org_name';
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->id = 'org_id';
        $org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org_site_membership = $this->getMockBuilder(OrganizationSiteMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tags = $this->getMockBuilder(Tags::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getOrganizationMemberships')
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
            ->willReturn($org_site_memberships);
        $org_site_memberships->expects($this->once())
            ->method('get')
            ->with($this->site->id)
            ->willReturn($org_site_membership);
        $org_site_membership->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($this->site);
        $org_site_membership->expects($this->once())
            ->method('getTags')
            ->with()
            ->willReturn($this->tags);
    }

    /**
     * Set the test to expect Organization::getName() and Site::getName()
     */
    protected function expectGetNames()
    {
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->site_name);
        $this->organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->org_name);
    }
}
