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
     * @var OrganizationUserMembership
     */
    protected $model;
    /**
     * @var array
     */
    protected $org_data;
    /**
     * @var string
     */
    protected $role;
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
        $this->org_data = (object)['id' => 'user_id',];
        $this->role = 'role';
        $this->user_data = [
            'id' => 'user ID',
            'profile' => (object)['firstname' => 'first name', 'lastname' => 'last name',],
            'email' => 'handle@domain.ext',
        ];
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new OrganizationUserMembership(
            (object)['user' => $this->user_data, 'role' => $this->role,],
            ['collection' => $this->collection,]
        );
    }

    /**
     * Tests the OrganizationUserMembership::delete() function
     */
    public function testDelete()
    {
        $user = $this->expectGetUser();
        $organization = $this->expectGetOrganization();

        $organization->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_user_membership',
                ['params' => ['user_id' => $user->id,],]
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
        $organization = $this->expectGetOrganization();
        $out = $this->model->getOrganization();
        $this->assertEquals($organization, $out);
    }

    /**
     * Tests the OrganizationUserMembership::getReferences() function.
     */
    public function testGetReferences()
    {
        $user = $this->expectGetUser();
        $user->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($this->user_data);

        $out = $this->model->getReferences();
        $this->assertEquals(array_merge([$this->model->id,], $this->user_data), $out);
    }

    /**
     * Tests the OrganizationUserMembership::getUser() function
     */
    public function testGetUser()
    {
        $user = $this->expectGetUser();
        $out = $this->model->getUser();
        $this->assertEquals($user, $out);
    }

    /**
     * Tests the OrganizationUserMembership::serialize() function
     */
    public function testSerialize()
    {
        $user_data = [
            'id' => $this->user_data['id'],
            'first_name' => $this->user_data['profile']->firstname,
            'last_name' => $this->user_data['profile']->lastname,
            'email' => $this->user_data['email'],
        ];
        $expected = array_merge($user_data, ['role' => $this->role,]);

        $user = $this->expectGetUser();
        $user->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($user_data);

        $out = $this->model->serialize();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests the OrganizationUserMembership::setRole() function
     */
    public function testSetRole()
    {
        $user = $this->expectGetUser();
        $organization = $this->expectGetOrganization();

        $organization->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('update_organization_user_membership'),
                $this->equalTo(['params' => ['user_id' => $user->id, 'role' => $this->role,],])
            )
            ->willReturn($this->workflow);

        $out = $this->model->setRole($this->role);
        $this->assertEquals($this->workflow, $out);
    }

    /**
     * Prepares the test case for the getOrganization() function.
     *
     * @return Organization The organization object getOrganization() will return
     */
    protected function expectGetOrganization()
    {
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization->id = 'organization ID';
        $organization->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->collection->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($organization);
        return $organization;
    }

    /**
     * Prepares the test case for the getUser() function.
     *
     * @return User The user object getUser() will return
     */
    protected function expectGetUser()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = $this->user_data['id'];

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(User::class),
                $this->equalTo([$this->user_data,])
            )
            ->willReturn($user);

        $this->model->setContainer($container);
        return $user;
    }
}
