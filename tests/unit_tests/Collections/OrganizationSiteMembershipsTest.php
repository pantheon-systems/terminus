<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Site;

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

    public function testGet()
    {
        $model_data = [
            'a' => (object)[
                'site' => new Site((object)['id' => 'abc', 'name' => 'Site A']),
                'organization_id' => '123',
                "role" => "team_member",
            ],
            'b' => (object)[
                'site' => new Site((object)['id' => 'bcd', 'name' => 'Site B']),
                'organization_id' => '123',
                "role" => "team_member",
            ],
            'c' => (object)[
                'site' => new Site((object)['id' => 'cde', 'name' => 'Site C']),
                'organization_id' => '123',
                "role" => "team_member",
            ],
        ];
        $models = [];
        foreach ($model_data as $id => $data) {
            $models[$id] = $this->getMockBuilder(OrganizationSiteMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $models[$id]->method('getSite')->willReturn($data->site);
        }

        $org_site_membership = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $org_site_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($models);

        $this->assertEquals($models['a'], $org_site_membership->get('a'));
        $this->assertEquals($models['b'], $org_site_membership->get('b'));
        $this->assertEquals($models['c'], $org_site_membership->get('c'));
        $this->assertEquals($models['a'], $org_site_membership->get('Site A'));
        $this->assertEquals($models['b'], $org_site_membership->get('Site B'));
        $this->assertEquals($models['a'], $org_site_membership->get('abc'));
        $this->assertEquals($models['c'], $org_site_membership->get('cde'));
        $this->setExpectedException(TerminusNotFoundException::class);
        $this->assertEquals(null, $org_site_membership->get('invalid'));


        $this->assertEquals($model_data['a']->site, $org_site_membership->getSite('a'));
        $this->assertEquals($model_data['a']->site, $org_site_membership->getSite('Site A'));
        $this->assertEquals($model_data['a']->site, $org_site_membership->getSite('abc'));
        $this->setExpectedException(TerminusException::class);
        $this->assertEquals(null, $org_site_membership->getSite('invalid'));

        $this->assertTrue($org_site_membership->siteIsMember('abc'));
        $this->assertTrue($org_site_membership->siteIsMember('Site B'));
        $this->assertFalse($org_site_membership->siteIsMember('invalid'));
    }
}
