<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Robo\Config;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Models\UserSiteMembership;

/**
 * Class UserTest
 * Testing class for Pantheon\Terminus\Models\User
 * @package Pantheon\Terminus\UnitTests\Models
 */
class UserTest extends ModelTestCase
{
    /**
     * @var User
     */
    protected $user;
    /**
     * @var array
     */
    protected $user_data;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->user_data = [
            'id' => '123',
            'email' => 'dev@example.com',
            'profile' => (object)[
                'firstname' => 'Peter',
                'lastname' => 'Pantheor',
                'full_name' => 'Peter Pantheor',
            ],
        ];
        $this->user = new User((object)$this->user_data);
        $this->user->setRequest($this->request);
    }

    /**
     * Tests the User::dashboardUrl() function
     */
    public function testDashboardUrl()
    {
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $config->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                $this->equalTo('dashboard_protocol'),
                $this->equalTo('dashboard_host')
            )
            ->willReturnOnConsecutiveCalls(
                'https',
                'dashboard.pantheon.io'
            );
        $this->user->setConfig($config);

        $this->assertEquals('https://dashboard.pantheon.io/users/123#sites', $this->user->dashboardUrl());
    }

    /**
     * Tests the User::getAliases() function
     */
    public function testGetAliases()
    {
        $aliases = ['foo', 'bar',];
        $this->request->expects($this->once())
            ->method('request')
            ->with("users/123/drush_aliases", ['method' => 'get',])
            ->willReturn(['data' => (object)['drush_aliases' => $aliases,],]);

        $out = $this->user->getAliases();
        $this->assertEquals($aliases, $out);
        // Confirm that it returns the same output twice without calling to the API twice.
        $this->assertEquals($aliases, $this->user->getAliases());
    }


    /**
     * Tests the User::getOrganizations() function
     */
    public function testGetOrganizations()
    {
        $memberships = [
            (object)[
                'id' => '1',
                'organization' => new Organization((object)[
                    'id' => 'org1',
                    'other' => 'abc',
                ])
            ],
            (object)[
                'id' => '2',
                'organization' => new Organization((object)[
                    'id' => 'org2',
                    'other' => 'cdf',
                ])
            ]
        ];
        $membs = [];
        foreach ($memberships as $i => $membership) {
            $membs[$i] = $this->getMockBuilder(UserOrganizationMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $membs[$i]->expects($this->any())
                ->method('getOrganization')
                ->willReturn($membership->organization);
        }
        $orgs = [
            'org1' => $memberships[0]->organization,
            'org2' => $memberships[1]->organization,
        ];

        $orgmemberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orgmemberships ->expects($this->once())
            ->method('all')
            ->willReturn($membs);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(UserOrganizationMemberships::class, [['user' => $this->user]])
            ->willReturn($orgmemberships);

        $this->user->setContainer($container);

        $this->assertEquals($orgs, $this->user->getOrganizations());
    }

    /**
     * Tests the User::getProfile() function
     */
    public function testGetProfile()
    {
        $this->assertEquals($this->user->getProfile(), $this->user_data['profile']);
    }

    /**
     * Tests the User::getName() function
     */
    public function testGetName()
    {
        $this->assertEquals($this->user_data['profile']->full_name, $this->user->getName());
    }

    /**
     * Tests the User::getSites() function
     */
    public function testGetSites()
    {
        $memberships_data = [
            (object)[
                'id' => '1',
                'site' => (object)[
                    'id' => 'site1',
                    'other' => 'abc',
                ],
            ],
            (object)[
                'id' => '2',
                'site' => (object)[
                    'id' => 'site2',
                    'other' => 'cdf',
                ],
            ]
        ];

        $memberships = [];
        foreach ($memberships_data as $membership_data) {
            $membership = $this->getMockBuilder(UserSiteMembership::class)
                ->disableOriginalConstructor()
                ->getMock();
            $site = $this->getMockBuilder(Site::class)
                ->disableOriginalConstructor()
                ->getMock();
            $site->method('get')
                ->with('id')
                ->willReturn($membership_data->site->id);

            $membership->method('getSite')
                ->willReturn($site);
            $memberships[] = $membership;
            $sites[$membership_data->site->id] = $site;
        }

        $sitememberships = $this->getMockBuilder(UserSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sitememberships->expects($this->once())
            ->method('all')
            ->willReturn($memberships);

        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->once())
            ->method('get')
            ->with(UserSiteMemberships::class, [['user' => $this->user]])
            ->willReturn($sitememberships);

        $this->user->setContainer($container);

        $this->assertEquals($sites, $this->user->getSites());
    }

    /**
     * Tests various User::get*() function
     */
    public function testGetSubCollections()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classes = [
            PaymentMethods::class,
            MachineTokens::class,
            UserOrganizationMemberships::class,
            UserSiteMemberships::class,
            SSHKeys::class,
            Upstreams::class,
            Workflows::class
        ];
        foreach ($classes as $i => $class) {
            $container->expects($this->at($i))
                ->method('get')
                ->with($class, [['user' => $this->user,],])
                ->willReturn(new $class(['user' => $this->user,]));
        }

        $this->user->setContainer($container);

        $this->user->getPaymentMethods();
        $this->user->getMachineTokens();
        $this->user->getOrgMemberships();
        $this->user->getSiteMemberships();
        $this->user->getSSHKeys();
        $this->user->getUpstreams();
        $this->user->getWorkflows();
    }

    /**
     * Tests the User::serialize() function
     */
    public function testSerialize()
    {
        $expected = array_merge($this->user_data, (array)$this->user_data['profile']);
        unset($expected['profile']);
        unset($expected['full_name']);

        $data = $this->user->serialize();
        $this->assertEquals($expected, $data);
    }
}
