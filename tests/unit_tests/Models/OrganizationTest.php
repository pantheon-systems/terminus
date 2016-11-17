<?php


namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\User;
use Terminus\Models\Site;

class OrganizationTest extends ModelTestCase
{
    public function testGetFeature()
    {
        $data = [
            "change_management" => true,
            "multidev" => false,
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'organizations/123/features',
                []
            )
            ->willReturn(['data' => $data]);

        $organization = new Organization((object)['id' => '123']);
        $organization->setRequest($this->request);

        $this->assertTrue($organization->getFeature('change_management'));
        $this->assertFalse($organization->getFeature('multidev'));
        $this->assertNull($organization->getFeature('invalid'));
    }

    public function testGetSites()
    {
        $organization = new Organization((object)['id' => '123']);

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
        $sites = [];
        foreach ($model_data as $model) {
            $sites[$model->site->id] = $model->site;
        }
        $org_site_membership = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $org_site_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($model_data);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(OrganizationSiteMemberships::class, [['organization' => $organization]])
            ->willReturn($org_site_membership);

        $organization->setContainer($container);

        $this->assertEquals($org_site_membership, $organization->getSiteMemberships());
        $this->assertEquals($sites, $organization->getSites());
    }

    public function testGetUsers()
    {
        $organization = new Organization((object)['id' => '123']);

        $user_data = [
            'a' => ['id' => 'abc', 'email' => 'a@example.com', 'profile' => (object)['full_name' => 'User A']],
            'b' => ['id' => 'bcd', 'email' => 'b@example.com', 'profile' => (object)['full_name' => 'User B']],
            'c' => ['id' => 'cde', 'email' => 'c@example.com', 'profile' => (object)['full_name' => 'User C']],
        ];
        $users = [];
        foreach ($user_data as $i => $user) {
            $model_data[$i] = $this->getMockBuilder(OrganizationUserMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $model_data[$i]->user = $users[$user['id']] = new User((object)$user);
        }

        $org_user_membership = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $org_user_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($model_data);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(OrganizationUserMemberships::class, [['organization' => $organization]])
            ->willReturn($org_user_membership);

        $organization->setContainer($container);

        $this->assertEquals($org_user_membership, $organization->getUserMemberships());
        $this->assertEquals($users, $organization->getUsers());
    }
}
