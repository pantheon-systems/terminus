<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;

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
}
