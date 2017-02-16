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
use Pantheon\Terminus\Models\User;

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
     * Tests the User::getReferences() function
     */
    public function testGetReferences()
    {
        $data = $this->user_data;
        $this->assertEquals([$data['id'], $data['profile']->full_name, $data['email'],], $this->user->getReferences());
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
        $this->user->getOrganizationMemberships();
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
