<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;

/**
 * Class OrganizationTest
 * Testing class for Pantheon\Terminus\Models\Organization
 * @package Pantheon\Terminus\UnitTests\Models
 */
class OrganizationTest extends ModelTestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the Organization::getFeature() function
     */
    public function testGetFeature()
    {
        $data = [
            'change_management' => true,
            'multidev' => false,
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'organizations/123/features',
                []
            )
            ->willReturn(['data' => $data,]);

        $organization = new Organization((object)['id' => '123',]);
        $organization->setRequest($this->request);

        $this->assertTrue($organization->getFeature('change_management'));
        $this->assertFalse($organization->getFeature('multidev'));
        $this->assertNull($organization->getFeature('invalid'));
    }

    /**
     * Tests the Organization::getName() function
     */
    public function testGetName()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $out = $organization->getName();
        $this->assertEquals($org_name, $out);
    }

    /**
     * Tests the Organization::getSiteMemberships() function
     */
    public function testGetSiteMemberships()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationSiteMemberships::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($org_site_memberships);
        $organization->setContainer($this->container);

        $out = $organization->getSiteMemberships();
        $this->assertEquals($org_site_memberships, $out);
    }

    /**
     * Tests the Organization::getUserMemberships() function
     */
    public function testGetUserMemberships()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $org_user_memberships = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationUserMemberships::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($org_user_memberships);
        $organization->setContainer($this->container);

        $out = $organization->getUserMemberships();
        $this->assertEquals($org_user_memberships, $out);
    }

    /**
     * Tests the Organization::getWorkflows() function
     */
    public function testGetWorkflows()
    {
        $org_name = 'organization name';
        $organization = new Organization((object)['id' => '123', 'profile' => (object)['name' => $org_name,],]);
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Workflows::class),
                $this->equalTo([['organization' => $organization,],])
            )
            ->willReturn($workflows);
        $organization->setContainer($this->container);

        $out = $organization->getWorkflows();
        $this->assertEquals($workflows, $out);
    }

    /**
     * Tests the Organization::serialize() and Organiztion::getReferences() functions
     */
    public function testSerialize()
    {
        $org_id = 'org id';
        $org_name = 'organization name';
        $expected = ['id' => $org_id, 'name' => $org_name,];
        $organization = new Organization((object)['id' => $org_id, 'profile' => (object)['name' => $org_name,],]);
        $this->assertEquals($expected, $organization->serialize());
        $this->assertEquals($expected, $organization->getReferences());
    }
}
