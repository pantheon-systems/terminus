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
use Pantheon\Terminus\Models\Profile;
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
     * @var Container
     */
    protected $container;
    /**
     * @var User
     */
    protected $model;
    /**
     * @var Profile
     */
    protected $profile;
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

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->profile = $this->getMockBuilder(Profile::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user_data = [
            'id' => '123',
            'email' => 'dev@example.com',
            'profile' => (object)[
                'firstname' => 'Peter',
                'lastname' => 'Pantheor',
                'full_name' => 'Peter Pantheor',
            ],
        ];
        $this->model = new User((object)$this->user_data);
        $this->model->setRequest($this->request);
        $this->model->setContainer($this->container);
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
        $this->model->setConfig($config);

        $this->assertEquals("https://dashboard.pantheon.io/users/{$this->user_data['id']}#sites", $this->model->dashboardUrl());
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

        $out = $this->model->getAliases();
        $this->assertEquals($aliases, $out);
        // Confirm that it returns the same output twice without calling to the API twice.
        $this->assertEquals($aliases, $this->model->getAliases());
    }

    /**
     * Tests the User::getName() function
     */
    public function testGetName()
    {
        $user_name = 'User Name';
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Profile::class),
                $this->equalTo([$this->user_data['profile'],])
            )
            ->willReturn($this->profile);
        $this->profile->expects($this->once())
            ->method('get')
            ->with($this->equalTo('full_name'))
            ->willReturn($user_name);

        $this->assertEquals($user_name, $this->model->getName());
    }

    /**
     * Tests the User::getReferences() function
     */
    public function testGetReferences()
    {
        $data = $this->user_data;
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Profile::class),
                $this->equalTo([$this->user_data['profile'],])
            )
            ->willReturn($this->profile);
        $this->profile->expects($this->once())
            ->method('get')
            ->with($this->equalTo('full_name'))
            ->willReturn($data['profile']->full_name);

        $this->assertEquals([$data['id'], $data['profile']->full_name, $data['email'],], $this->model->getReferences());
    }

    /**
     * Tests various User::get*() function
     */
    public function testGetSubCollections()
    {
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
            $this->container->expects($this->at($i))
                ->method('get')
                ->with($class, [['user' => $this->model,],])
                ->willReturn(new $class(['user' => $this->model,]));
        }

        $this->model->getPaymentMethods();
        $this->model->getMachineTokens();
        $this->model->getOrganizationMemberships();
        $this->model->getSiteMemberships();
        $this->model->getSSHKeys();
        $this->model->getUpstreams();
        $this->model->getWorkflows();
    }

    /**
     * Tests the User::serialize() function
     */
    public function testSerialize()
    {
        $data = $this->user_data;
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Profile::class),
                $this->equalTo([$this->user_data['profile'],])
            )
            ->willReturn($this->profile);
        $this->profile->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('firstname'))
            ->willReturn($data['profile']->firstname);
        $this->profile->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('lastname'))
            ->willReturn($data['profile']->lastname);

        $expected = [
            'firstname' => $data['profile']->firstname,
            'lastname' => $data['profile']->lastname,
            'email' => $data['email'],
            'id' => $data['id'],
        ];

        $this->assertEquals($expected, $this->model->serialize());
    }
}
