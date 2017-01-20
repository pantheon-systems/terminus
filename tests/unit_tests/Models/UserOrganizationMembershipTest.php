<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class UserOrganizationMembershipTest
 * Testing class for Pantheon\Terminus\Models\UserOrganizationMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class UserOrganizationMembershipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserOrganizationMemberships
     */
    protected $collection;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var array
     */
    protected $org_data;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_data = [
            'id' => 'org id',
            'name' => 'org name',
        ];
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization->id = $this->org_data['id'];
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->id = 'user ID';

        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);

        $this->model = new UserOrganizationMembership(
            (object)['organization' => $this->org_data,],
            ['collection' => (object)$this->collection,]
        );
        $this->model->setContainer($this->container);
    }

    /**
     * Tests the UserOrganizationMemberships::getOrganization() function
     */
    public function testGetOrganization()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Organization::class),
                $this->equalTo([$this->org_data,])
            )
            ->willReturn($this->organization);

        $out = $this->model->getOrganization();
        $this->assertEquals($this->organization, $out);
        $this->assertEquals([$this->model,], $this->organization->memberships);
    }

    /**
     * Tests the UserOrganizationMemberships::getUser() function
     */
    public function testGetUser()
    {
        $out = $this->model->getUser();
        $this->assertEquals($this->user, $out);
    }
}
