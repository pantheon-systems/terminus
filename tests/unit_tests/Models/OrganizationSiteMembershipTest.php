<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\Tags;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class OrganizationSiteMembershipTest
 * Testing class for Pantheon\Terminus\Models\Organization
 * @package Pantheon\Terminus\UnitTests\Models
 */
class OrganizationSiteMembershipTest extends ModelTestCase
{
    /**
     * @var OrganizationSiteMemberships
     */
    protected $collection;
    /**
     * @var OrganizationSiteMembership
     */
    protected $model;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var array
     */
    protected $site_data;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(OrganizationSiteMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_data = ['id' => 'site id', 'name' => 'site name', 'label' => 'site label',];

        $this->model = new OrganizationSiteMembership(
            (object)['site' => $this->site_data, 'tags' => (object)[],],
            ['collection' => $this->collection,]
        );
    }

    /**
     * Tests the UserSiteMemberships::__toString() function.
     */
    public function testToString()
    {
        $org_name = 'org name';
        $organization = $this->expectGetOrganization();
        $organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($org_name);

        $this->assertEquals("{$organization->id}: $org_name", (string)$this->model);
    }

    /**
     * Tests the UserSiteMemberships::delete() function.
     */
    public function testDelete()
    {
        $site_data = ['site_id' => '123',];
        $container = new Container();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = $site_data['site_id'];
        $container->add(Site::class, $site);
        $container->add(Tags::class);

        $organization = $this->expectGetOrganization();
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $organization->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($workflows);
        $workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_site_membership',
                ['params' => $site_data,]
            )
            ->willReturn($workflow);

        $this->model->setContainer($container);
        $out = $this->model->delete();
        $this->assertEquals($workflow, $out);
    }

    /**
     * Tests the UserSiteMemberships::getOrganization() function.
     */
    public function testGetOrganization()
    {
        $organization = $this->expectGetOrganization();
        $this->assertEquals($organization, $this->model->getOrganization());
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
        $this->collection->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($organization);
        return $organization;
    }
}
