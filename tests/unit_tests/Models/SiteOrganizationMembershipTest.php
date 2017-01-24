<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteOrganizationMembershipTest
 * Testing class for Pantheon\Terminus\Models\SiteOrganizationMembership
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SiteOrganizationMembershipTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteOrganizationMemberships
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
     * @var Site
     */
    protected $site;
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

        $this->collection = $this->getMockBuilder(SiteOrganizationMemberships::class)
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
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site ID';
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($this->site);

        $this->model = new SiteOrganizationMembership(
            (object)['organization' => $this->org_data,],
            ['collection' => (object)$this->collection,]
        );
        $this->model->id = 'model id';
        $this->model->setContainer($this->container);
    }

    /**
     * Tests the SiteOrganizationMemberships::delete() function
     */
    public function testDelete()
    {
        $this->site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('remove_site_organization_membership'),
                $this->equalTo(['params' => ['organization_id' => $this->model->id,],])
            )
            ->willReturn($this->workflow);

        $out = $this->model->delete();
        $this->assertEquals($this->workflow, $out);
    }

    /**
     * Tests the SiteOrganizationMemberships::getOrganization() function
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
     * Tests the SiteOrganizationMemberships::getSite() function
     */
    public function testGetSite()
    {
        $out = $this->model->getSite();
        $this->assertEquals($this->site, $out);
    }

    /**
     * Tests the SiteOrganizationMemberships::serialize() function
     */
    public function testSerialize()
    {
        $site_name = 'site name';
        $expected = [
            'org_id' => $this->organization->id,
            'org_name' => $this->org_data['name'],
            'site_id' => $this->site->id,
            'site_name' => $site_name,
        ];

        $this->container->expects($this->once())
            ->method('get')
            ->with(
                $this->equalTo(Organization::class),
                $this->equalTo([$this->org_data,])
            )
            ->willReturn($this->organization);
        $this->organization->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)$this->org_data);
        $this->site->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($site_name);

        $out = $this->model->serialize();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests the SiteOrganizationMemberships::setRole() function
     */
    public function testSetRole()
    {
        $role = 'role';

        $this->site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('update_site_organization_membership'),
                $this->equalTo(['params' => ['organization_id' => $this->model->id, 'role' => $role,],])
            )
            ->willReturn($this->workflow);

        $out = $this->model->setRole($role);
        $this->assertEquals($this->workflow, $out);
    }
}
