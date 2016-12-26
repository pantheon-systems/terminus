<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteUserMembershipTest
 * Testing class for Pantheon\Terminus\Models\SiteUserMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteUserMembershipTest extends \PHPUnit_Framework_TestCase
{
    protected $workflow;
    protected $site;
    protected $workflows;
    protected $site_user;

    public function setUp()
    {
        parent::setUp();

        $user_data = [
            'id' => 'abc',
            'firstname' => 'Daisy',
            'lastname' => 'Duck',
            'email' => 'daisy@duck.com',
        ];
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->method('serialize')
            ->willReturn([
                'id' => 'abc',
                'firstname' => 'Daisy',
                'lastname' => 'Duck',
                'email' => 'daisy@duck.com',
            ]);
        $this->user->id = 'abc';

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('get')
            ->with(User::class, [$user_data])
            ->willReturn($this->user);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->method('getWorkflows')->willReturn($this->workflows);

        $this->site_user = new SiteUserMembership(
            (object)['user' => $user_data, 'role' => 'team_member'],
            ['collection' => (object)['site' => $this->site]]
        );
        $this->site_user->setContainer($container);
    }

    public function testDelete()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_site_user_membership',
                ['params' => ['user_id' => 'abc']]
            )
            ->willReturn($this->workflow);

        $out = $this->site_user->delete();
        $this->assertEquals($this->workflow, $out);
    }

    public function testSetRole()
    {
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'update_site_user_membership',
                ['params' => ['user_id' => 'abc', 'role' => 'testrole']]
            )
            ->willReturn($this->workflow);

        $out = $this->site_user->setRole('testrole');
        $this->assertEquals($this->workflow, $out);
    }

    public function testSerialize()
    {
        $expected = [
            'firstname' => 'Daisy',
            'lastname' => 'Duck',
            'email' => 'daisy@duck.com',
            'id' => 'abc',
            'role' => 'team_member',
        ];
        $actual = $this->site_user->serialize();
        $this->assertEquals($expected, $actual);
    }
}
