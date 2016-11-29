<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\SiteUserMembership;

/**
 * Class SiteUserMembershipsTest
 * Testing class for Pantheon\Terminus\Collections\SiteUserMemberships
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SiteUserMembershipsTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->expects($this->once())
            ->method('getWorkflows')
            ->willReturn($workflows);

        $site->id = '123';

        $workflows->expects($this->once())
            ->method('create')
            ->with(
                'add_site_user_membership',
                ['params' => ['user_email' => 'dev@example.com', 'role' => 'team_member']]
            );

        $org_site_membership = new SiteUserMemberships(['site' => $site]);
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
            $model_data[$i] = $this->getMockBuilder(SiteUserMembership::class)
                ->disableOriginalConstructor()
                ->getMock();

            $model_data[$i]->expects($this->any())
                ->method('getUser')
                ->willReturn(new User((object)$user));
        }

        $site_user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->setMethods(['getMembers'])
            ->disableOriginalConstructor()
            ->getMock();

        $site_user_memberships->expects($this->any())
            ->method('getMembers')
            ->willReturn($model_data);

        $this->assertEquals($model_data['a'], $site_user_memberships->get('a'));
        $this->assertEquals($model_data['b'], $site_user_memberships->get('b'));
        $this->assertEquals($model_data['c'], $site_user_memberships->get('c'));
        $this->assertEquals($model_data['a'], $site_user_memberships->get('User A'));
        $this->assertEquals($model_data['b'], $site_user_memberships->get('User B'));
        $this->assertEquals($model_data['a'], $site_user_memberships->get('a@example.com'));
        $this->assertEquals($model_data['c'], $site_user_memberships->get('c@example.com'));
    }
}
