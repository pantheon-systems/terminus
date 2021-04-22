<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\UserOrganizationMembership;

/**
 * Class UpstreamsTest
 * Testing class for Pantheon\Terminus\Collections\Upstreams
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class UpstreamsTest extends CollectionTestCase
{
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var UserOrganizationMembership
     */
    protected $user_org_membership;
    /**
     * @var UserOrganizationMemberships
     */
    protected $user_org_memberships;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->user->method('getOrganizationMemberships')
            ->with()
            ->willReturn($this->user_org_memberships);
        $this->user_org_membership->method('getOrganization')
            ->willReturn($this->organization);

        $this->collection = new Upstreams(['user' => $this->user,]);
        $this->collection->setContainer($this->container);
    }

    /**
     * Tests the Upstreams::add(object, array) and Upstreams::getOrganizationMemberships(Organization[]) functions
     */
    public function testAdd()
    {
        $data = [
            'a' => (object)['id' => 'a', 'organization_id' => '',],
            'b' => (object)['id' => 'b', 'organization_id' => 'DNE',],
            'c' => (object)['id' => 'c', 'organization_id' => 'orgID',],
        ];
        $org = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $i = 0;
        foreach ($data as $model_data) {
            $options = ['collection' => $this->collection, 'id' => $model_data->id,];
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(Upstream::class, [$model_data, $options,])
                ->willReturn(new Upstream($model_data, $options));
        }
        $this->user_org_memberships->expects($this->at(0))
            ->method('get')
            ->with($data['b']->organization_id)
            ->will($this->throwException(new TerminusNotFoundException()));
        $this->user_org_memberships->expects($this->at(1))
            ->method('get')
            ->with($data['c']->organization_id)
            ->willReturn($this->user_org_membership);
        $this->user_org_membership->expects($this->once())
            ->method('getOrganization')
            ->willReturn($this->user_org_membership);

        $upstreams = [];
        foreach ($data as $model_data) {
            $upstreams[$model_data->id] = $this->collection->add($model_data);
            $this->assertEquals($model_data->id, $upstreams[$model_data->id]->id);
        }
        $this->assertNull($upstreams['a']->getOrganization());
        $this->assertNull($upstreams['b']->getOrganization());
        $this->assertEquals($org, $upstreams['c']->getOrganization());
    }

    /**
     * Tests the Upstreams::filterByName(string) function
     */
    public function testFilterByName()
    {
        $data = [
            'a' => (object)['id' => 'a', 'label' => 'WordPress', 'organization_id' => '',],
            'b' => (object)['id' => 'b', 'label' => 'Drupal 7', 'organization_id' => '',],
            'c' => (object)['id' => 'c', 'label' => 'Drupal8', 'organization_id' => '',],
        ];
        $i = 0;
        foreach ($data as $model_data) {
            $options = ['collection' => $this->collection, 'id' => $model_data->id,];
            $this->container->expects($this->at($i++))
                ->method('get')
                ->with(Upstream::class, [$model_data, $options,])
                ->willReturn(new Upstream($model_data, $options));
        }
        foreach ($data as $model_data) {
            $this->collection->add($model_data);
        }
        $unfiltered = $this->collection->all();
        $drupal_only = $this->collection->filterByName('Drupal')->all();

        $this->assertEquals(count($data), count($unfiltered));
        $this->assertEquals(2, count($drupal_only));

        array_shift($unfiltered);
        $this->assertEquals($unfiltered, $drupal_only);
    }
}
