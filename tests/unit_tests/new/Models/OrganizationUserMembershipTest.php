<?php


namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;

class OrganizationUserMembershipTest extends \PHPUnit_Framework_TestCase
{
    public function testDelete()
    {
        $user_data = (object)['id' => '234'];
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('get')
            ->with(User::class, [$user_data])
            ->willReturn(new User($user_data));

        $wf = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $org = new Organization((object)['id' => '123', 'profile' => (object)['name' => 'My Org']]);
        $org->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $org->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_user_membership',
                ['params' => ['user_id' => '234']]
            )
            ->willReturn($wf);

        $org_site = new OrganizationUserMembership(
            (object)['user' => $user_data],
            ['collection' => (object)['organization' => $org]]
        );
        $org_site->setContainer($container);
        $out = $org_site->delete();
        $this->assertEquals($wf, $out);
    }

    public function testSetRole()
    {
        $user_data = (object)['id' => '234'];
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('get')
            ->with(User::class, [$user_data])
            ->willReturn(new User($user_data));

        $wf = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $org = new Organization((object)['id' => '123', 'profile' => (object)['name' => 'My Org']]);
        $org->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $org->workflows->expects($this->once())
            ->method('create')
            ->with(
                'update_organization_user_membership',
                ['params' => ['user_id' => '234', 'role' => 'testrole']]
            )
            ->willReturn($wf);

        $org_site = new OrganizationUserMembership(
            (object)['user' => $user_data],
            ['collection' => (object)['organization' => $org]]
        );
        $org_site->setContainer($container);
        $out = $org_site->setRole('testrole');
        $this->assertEquals($wf, $out);
    }
}
