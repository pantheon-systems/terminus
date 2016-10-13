<?php


namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\User;
use Terminus\Models\OrganizationUserMembership;

class OrganizationUserMembershipsTest extends CollectionTestCase
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

        $organization->id = '123';

        $workflows->expects($this->once())
            ->method('create')
            ->with(
                'add_organization_user_membership',
                ['params' => ['user_email' => 'dev@example.com', 'role' => 'team_member']]
            );

        $org_site_membership = new OrganizationUserMemberships(['organization' => $organization]);
        $org_site_membership->create('dev@example.com', 'team_member');
    }

    public function testGet()
    {
        $user_data = [
            'a' => ['id' => 'abc', 'email' => 'a@example.com', 'profile' => (object)['full_name' => 'User A']],
            'b' => ['id' => 'bcd', 'email' => 'b@example.com', 'profile' => (object)['full_name' => 'User B']],
            'c' => ['id' => 'cde', 'email' => 'c@example.com', 'profile' => (object)['full_name' => 'User C']],
        ];

        foreach ($user_data as $i => $user) {
            $model_data[$i] = $this->getMockBuilder(OrganizationUserMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $model_data[$i]->expects($this->any())
                ->method('get')
                ->with('user')
                ->willReturn(new User((object)$user));
        }

        $org_user_membership = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $org_user_membership->expects($this->any())
            ->method('getMembers')
            ->willReturn($model_data);

        $this->assertEquals($model_data['a'], $org_user_membership->get('a'));
        $this->assertEquals($model_data['b'], $org_user_membership->get('b'));
        $this->assertEquals($model_data['c'], $org_user_membership->get('c'));
        $this->assertEquals($model_data['a'], $org_user_membership->get('User A'));
        $this->assertEquals($model_data['b'], $org_user_membership->get('User B'));
        $this->assertEquals($model_data['a'], $org_user_membership->get('a@example.com'));
        $this->assertEquals($model_data['c'], $org_user_membership->get('c@example.com'));
    }
}
