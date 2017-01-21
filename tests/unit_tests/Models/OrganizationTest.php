<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\OrganizationUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Site;

/**
 * Class OrganizationTest
 * Testing class for Pantheon\Terminus\Models\Organization
 * @package Pantheon\Terminus\UnitTests\Models
 */
class OrganizationTest extends ModelTestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the Organization::getFeature() function
     */
    public function testGetFeature()
    {
        $data = [
            'change_management' => true,
            'multidev' => false,
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'organizations/123/features',
                []
            )
            ->willReturn(['data' => $data,]);

        $organization = new Organization((object)['id' => '123',]);
        $organization->setRequest($this->request);

        $this->assertTrue($organization->getFeature('change_management'));
        $this->assertFalse($organization->getFeature('multidev'));
        $this->assertNull($organization->getFeature('invalid'));
    }

    /**
     * Tests the Organization::getName() function
     */
    public function testGetName()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $out = $organization->getName();
        $this->assertEquals($org_name, $out);
    }

    /**
     * Tests the Organization::getSites() function
     */
    public function testGetSites()
    {
        $organization = new Organization((object)['id' => '123',]);

        $model_data = [
            'a' => (object)[
                'site' => new Site((object)['id' => 'abc', 'name' => 'Site A',]),
                'organization_id' => '123',
                'role' => 'team_member',
            ],
            'b' => (object)[
                'site' => new Site((object)['id' => 'bcd', 'name' => 'Site B',]),
                'organization_id' => '123',
                'role' => 'team_member',
            ],
            'c' => (object)[
                'site' => new Site((object)['id' => 'cde', 'name' => 'Site C',]),
                'organization_id' => '123',
                'role' => 'team_member',
            ],
        ];
        $models = $sites = [];
        foreach ($model_data as $id => $data) {
            $models[$id] = $this->getMockBuilder(OrganizationSiteMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $models[$id]->method('getSite')->willReturn($data->site);
            $sites[$data->site->id] = $data->site;
        }
        $org_site_membership = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $org_site_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($models);

        $this->container->expects($this->once())
            ->method('get')
            ->with(OrganizationSiteMemberships::class, [['organization' => $organization,],])
            ->willReturn($org_site_membership);

        $organization->setContainer($this->container);

        $this->assertEquals($org_site_membership, $organization->getSiteMemberships());
        $this->assertEquals($sites, $organization->getSites());
    }

    /**
     * Tests the Organization::getSiteMemberships() function
     */
    public function testSiteMemberships()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationSiteMemberships::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($org_site_memberships);
        $organization->setContainer($this->container);

        $out = $organization->getSiteMemberships();
        $this->assertEquals($org_site_memberships, $out);
    }

    /**
     * Tests the Organization::getUserMemberships() function
     */
    public function testUserMemberships()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $org_user_memberships = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationUserMemberships::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($org_user_memberships);
        $organization->setContainer($this->container);

        $out = $organization->getUserMemberships();
        $this->assertEquals($org_user_memberships, $out);
    }

    /**
     * Tests the Organization::getUsers() function
     */
    public function testGetUsers()
    {
        $organization = new Organization((object)['id' => '123',]);

        $user_data = [
            'a' => ['id' => 'abc', 'email' => 'a@example.com', 'profile' => (object)['full_name' => 'User A',],],
            'b' => ['id' => 'bcd', 'email' => 'b@example.com', 'profile' => (object)['full_name' => 'User B',],],
            'c' => ['id' => 'cde', 'email' => 'c@example.com', 'profile' => (object)['full_name' => 'User C',],],
        ];
        $model_data = $users = [];
        foreach ($user_data as $i => $user) {
            $model_data[$i] = $this->getMockBuilder(OrganizationUserMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $users[$user['id']] = new User((object)$user);
            $model_data[$i]->method('getUser')->willReturn($users[$user['id']]);
        }

        $org_user_membership = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->setMethods(['getMembers',])
            ->disableOriginalConstructor()
            ->getMock();
        $org_user_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($model_data);
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(OrganizationUserMemberships::class, [['organization' => $organization,],])
            ->willReturn($org_user_membership);
        $organization->setContainer($this->container);

        $this->assertEquals($org_user_membership, $organization->getUserMemberships());
        $this->assertEquals($users, $organization->getUsers());
    }

    /**
     * Tests the Organization::getWorkflows() function
     */
    public function testGetWorkflows()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Workflows::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($workflows);
        $organization->setContainer($this->container);

        $out = $organization->getWorkflows();
        $this->assertEquals($workflows, $out);
    }

    /**
     * Tests the Organization::serialize() function
     */
    public function testSerialize()
    {
        $org_id = 'org id';
        $org_name = 'organization name';
        $expected = ['id' => $org_id, 'name' => $org_name,];
        $organization = new Organization((object)['id' => $org_id, 'profile' => (object)['name' => $org_name,],]);
        $out = $organization->serialize();
        $this->assertEquals($expected, $out);
    }
}
