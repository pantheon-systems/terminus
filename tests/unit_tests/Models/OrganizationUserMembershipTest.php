<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUserMembership;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class OrganizationUserMembershipTest
 * Testing class for Pantheon\Terminus\Models\OrganizationUserMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class OrganizationUserMembershipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationUserMemberships
     */
    protected $collection;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var OrganizationUserMembership
     */
    protected $model;
    /**
     * @var array
     */
    protected $org_data;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var string
     */
    protected $role;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var object
     */
    protected $user_data;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_data = (object)['id' => 'user_id',];
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->role = 'role';
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_data = (object)[
            'id' => 'user ID',
            'profile' => (object)['firstname' => 'first name', 'lastname' => 'last name',],
            'email' => 'handle@domain.ext',
        ];
        $this->user->id = $this->user_data->id;
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($this->organization);

        $this->model = new OrganizationUserMembership(
            (object)['user' => $this->user_data, 'role' => $this->role,],
            ['collection' => $this->collection,]
        );
        $this->model->setContainer($this->container);
    }

    /**
     * Tests the OrganizationUserMembership::delete() function
     */
    public function testDelete()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($this->user);
        $this->organization->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_user_membership',
                ['params' => ['user_id' => $this->user_data->id,],]
            )
            ->willReturn($this->workflow);

        $out = $this->model->delete();
        $this->assertEquals($this->workflow, $out);
    }

    /**
     * Tests the OrganizationUserMembership::getOrganization() function
     */
    public function testGetOrganization()
    {
        $out = $this->model->getOrganization();
        $this->assertEquals($this->organization, $out);
    }

    /**
     * Tests the OrganizationUserMembership::getUser() function
     */
    public function testGetUser()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($this->user);

        $out = $this->model->getUser();
        $this->assertEquals($this->user, $out);
    }

    /**
     * Tests the OrganizationUserMembership::serialize() function
     */
    public function testSerialize()
    {
        $expected = [
            'id' => $this->user_data->id,
            'first_name' => $this->user_data->profile->firstname,
            'last_name' => $this->user_data->profile->lastname,
            'email' => $this->user_data->email,
            'role' => $this->role,
        ];

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($this->user);
        $this->user->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn($this->user_data->profile);
        $this->user->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('email'))
            ->willReturn($this->user_data->email);

        $out = $this->model->serialize();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests the OrganizationUserMembership::setRole() function
     */
    public function testSetRole()
    {
        $role = 'role';

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($this->user);
        $this->organization->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('update_organization_user_membership'),
                $this->equalTo(['params' => ['user_id' => $this->user->id, 'role' => $role,],])
            )
            ->willReturn($this->workflow);

        $out = $this->model->setRole($role);
        $this->assertEquals($this->workflow, $out);
    }
}
