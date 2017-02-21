<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;

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
     * @var UserOrganizationMembership
     */
    protected $model;
    /**
     * @var array
     */
    protected $org_data;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_data = [
            'id' => 'org id',
            'name' => 'org name',
        ];

        $this->model = new UserOrganizationMembership(
            (object)['organization' => $this->org_data,],
            ['collection' => (object)$this->collection,]
        );
    }

    /**
     * Tests the UserOrganizationMemberships::getOrganization() function
     */
    public function testGetOrganization()
    {
        $organization = $this->expectGetOrganization();
        $out = $this->model->getOrganization();
        $this->assertEquals($organization, $out);
        $this->assertEquals([$this->model,], $organization->memberships);
    }

    /**
     * Tests the SiteOrganizationMembership::getReferences() function.
     */
    public function testGetReferences()
    {
        $organization = $this->expectGetOrganization();
        $organization->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($this->org_data);

        $out = $this->model->getReferences();
        $this->assertEquals(array_merge([$this->model->id,], $this->org_data), $out);
    }

    /**
     * Tests the UserOrganizationMemberships::getUser() function
     */
    public function testGetUser()
    {
        $user = $this->expectGetUser();
        $out = $this->model->getUser();
        $this->assertEquals($user, $out);
    }

    /**
     * Prepares the test case for the getOrganization() function.
     *
     * @return Organization The organization object getOrganization() will return
     */
    protected function expectGetOrganization()
    {
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $organization->id = $this->org_data['id'];

        $container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Organization::class),
                $this->equalTo([$this->org_data,])
            )
            ->willReturn($organization);

        $this->model->setContainer($container);
        return $organization;
    }

    /**
     * Prepares the test case for the getUser() function.
     *
     * @return User The user object getUser() will return
     */
    protected function expectGetUser()
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = 'user ID';
        $this->collection->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        return $user;
    }
}
