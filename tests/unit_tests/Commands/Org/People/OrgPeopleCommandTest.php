<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\People;

use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class OrgPeopleCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Org\People
 */
abstract class OrgPeopleCommandTest extends CommandTestCase
{
    /**
     * @var OrganizationUserMembership
     */
    protected $org_user_membership;
    /**
     * @var OrganizationUserMemberships
     */
    protected $org_user_memberships;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var UserOrganizationMembership
     */
    protected $user_org_membership;
    /**
     * @var UserOrganizationMemberships
     */
    protected $user_org_memberships;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->id = 'org_id';
        $this->org_user_membership = $this->getMockBuilder(OrganizationUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_user_memberships = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->method('getOrganizationMemberships')
            ->with()
            ->willReturn($this->user_org_memberships);
        $this->user_org_memberships->method('get')
            ->with($this->equalTo($this->organization->id))
            ->willReturn($this->user_org_membership);
        $this->user_org_membership->method('getOrganization')
            ->with()
            ->willReturn($this->organization);
        $this->organization->method('getUserMemberships')
            ->with()
            ->willReturn($this->org_user_memberships);
        $this->org_user_memberships->method('fetch')
            ->with()
            ->willReturn($this->org_user_memberships);
    }
}
