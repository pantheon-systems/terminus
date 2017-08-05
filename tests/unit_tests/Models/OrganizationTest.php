<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Profile;

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
     * @var Organization
     */
    protected $model;
    /**
     * @var Profile
     */
    protected $profile;

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

        $this->model = new Organization();
        $this->model->setContainer($this->container);
    }

    /**
     * Tests the Organization::getFeature() function
     */
    public function testGetFeature()
    {
        $org_id = 'org id';
        $data = [
            'change_management' => true,
            'multidev' => false,
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                "organizations/$org_id/features",
                []
            )
            ->willReturn(['data' => $data,]);

        $this->model->id = $org_id;
        $this->model->setRequest($this->request);

        $this->assertTrue($this->model->getFeature('change_management'));
        $this->assertFalse($this->model->getFeature('multidev'));
        $this->assertNull($this->model->getFeature('invalid'));
    }

    /**
     * Tests the Organization::getLabel() function
     */
    public function testGetLabel()
    {
        $org_label = 'Organization Label';
        $this->container->expects($this->once())
            ->method('get')
            ->with(Profile::class)
            ->willReturn($this->profile);
        $this->profile->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($org_label);

        $this->assertEquals($org_label, $this->model->getLabel());
    }

    /**
     * Tests the Organization::getName() function
     */
    public function testGetName()
    {
        $org_name = 'org-name';
        $this->container->expects($this->once())
            ->method('get')
            ->with(Profile::class)
            ->willReturn($this->profile);
        $this->profile->expects($this->once())
            ->method('get')
            ->with($this->equalTo('machine_name'))
            ->willReturn($org_name);

        $this->assertEquals($org_name, $this->model->getName());
    }


    /**
     * Tests the Organization::serialize() and Organization::getReferences() functions
     */
    public function testGetReferences()
    {
        $name = 'organization-name';
        $label = 'Organization Label';
        $this->model->id = 'org id';
        $expected = ['id' => $this->model->id, 'name' => $name, 'label' => $label,];

        $this->container->expects($this->once())
            ->method('get')
            ->with(Profile::class)
            ->willReturn($this->profile);
        $this->profile->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('machine_name'))
            ->willReturn($name);
        $this->profile->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($label);

        $this->assertEquals($expected, $this->model->getReferences());
    }

    /**
     * Tests the Organization::getSiteMemberships() function
     */
    public function testGetSiteMemberships()
    {
        $org_site_memberships = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationSiteMemberships::class),
                $this->equalTo([['organization' => $this->model,],])
            )
            ->willReturn($org_site_memberships);

        $this->assertEquals($org_site_memberships, $this->model->getSiteMemberships());
    }

    /**
     * Tests the Organization::getUpstreams() function
     */
    public function testGetUpstreams()
    {
        $upstreams = $this->getMockBuilder(OrganizationUpstreams::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationUpstreams::class),
                $this->equalTo([['organization' => $this->model,],])
            )
            ->willReturn($upstreams);

        $this->assertEquals($upstreams, $this->model->getUpstreams());
    }

    /**
     * Tests the Organization::getUserMemberships() function
     */
    public function testGetUserMemberships()
    {
        $org_user_memberships = $this->getMockBuilder(OrganizationUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(OrganizationUserMemberships::class),
                $this->equalTo([['organization' => $this->model,],])
            )
            ->willReturn($org_user_memberships);

        $this->assertEquals($org_user_memberships, $this->model->getUserMemberships());
    }

    /**
     * Tests the Organization::getWorkflows() function
     */
    public function testGetWorkflows()
    {
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Workflows::class),
                $this->equalTo([['organization' => $this->model,],])
            )
            ->willReturn($workflows);

        $this->assertEquals($workflows, $this->model->getWorkflows());
    }

    /**
     * Tests the Organization::serialize() function
     */
    public function testSerialize()
    {
        $name = 'organization-name';
        $label = 'Organization Label';
        $this->model->id = 'org id';
        $expected = ['id' => $this->model->id, 'name' => $name, 'label' => $label,];

        $this->container->expects($this->once())
            ->method('get')
            ->with(Profile::class)
            ->willReturn($this->profile);
        $this->profile->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('machine_name'))
            ->willReturn($name);
        $this->profile->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($label);

        $this->assertEquals($expected, $this->model->serialize());
    }

    /**
     * Tests the Organization::__toString() function
     */
    public function testToString()
    {
        $label = 'Organization Label';
        $this->model->id = 'org id';
        $expected = "{$this->model->id}: $label";

        $this->container->expects($this->once())
            ->method('get')
            ->with(Profile::class)
            ->willReturn($this->profile);
        $this->profile->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($label);

        $this->assertEquals($expected, $this->model->__toString());
    }
}
