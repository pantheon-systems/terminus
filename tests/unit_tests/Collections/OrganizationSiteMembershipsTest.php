<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;

/**
 * Class OrganizationSiteMembershipsTest
 * Testing class for Pantheon\Terminus\Collections\OrganizationSiteMemberships
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class OrganizationSiteMembershipsTest extends CollectionTestCase
{
    public function testCreate()
    {
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $organization->expects($this->once())
            ->method('getWorkflows')
            ->willReturn($workflows);

        $site = (object)['id' => '123'];

        $workflows->expects($this->once())
            ->method('create')
            ->with('add_organization_site_membership', ['params' => ['site_id' => '123', 'role' => 'team_member']]);

        $org_site_membership = new OrganizationSiteMemberships(['organization' => $organization]);
        $org_site_membership->create($site);
    }
}
