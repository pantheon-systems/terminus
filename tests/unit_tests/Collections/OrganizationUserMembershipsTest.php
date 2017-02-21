<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;

/**
 * Class OrganizationUserMembershipsTest
 * Testing class for Pantheon\Terminus\Collections\OrganizationUserMemberships
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class OrganizationUserMembershipsTest extends CollectionTestCase
{
    public function testCreate()
    {
        $params = ['user_email' => 'dev@example.com', 'role' => 'team_member',];
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization->id = '123';
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $organization->expects($this->once())
            ->method('getWorkflows')
            ->willReturn($workflows);
        $workflows->expects($this->once())
            ->method('create')
            ->with('add_organization_user_membership', compact('params'));

        $org_site_membership = new OrganizationUserMemberships(compact('organization'));
        $org_site_membership->create($params['user_email'], $params['role']);
    }
}
