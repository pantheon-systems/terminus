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
        $this->org_data = [
            'id' => 'org id',
            'name' => 'org name',
            'label' => 'Org Name',
        ];
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();


        $this->model = new SiteOrganizationMembership(
            (object)['id' => 'model id', 'organization' => $this->org_data,],
            ['collection' => (object)$this->collection,]
        );
    }

    /**
     * Tests the SiteOrganizationMemberships::delete() function
     */
    public function testDelete()
    {
        $site = $this->expectGetSite();
        $site->expects($this->once())
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
     * Tests the SiteOrganizationMemberships::getSite() function
     */
    public function testGetSite()
    {
        $site = $this->expectGetSite();
        $out = $this->model->getSite();
        $this->assertEquals($site, $out);
    }

    /**
     * Tests the SiteOrganizationMemberships::serialize() function
     */
    public function testSerialize()
    {
        $site_name = 'site name';
        $organization = $this->expectGetOrganization();
        $site = $this->expectGetSite();
        $expected = [
            'org_id' => $organization->id,
            'org_name' => $this->org_data['label'],
            'site_id' => $site->id,
            'site_name' => $site_name,
        ];

        $organization->expects($this->once())
            ->method('getName')
            ->with()
            ->willReturn($this->org_data['label']);
        $site->expects($this->once())
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
        $site = $this->expectGetSite();

        $site->expects($this->once())
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
     * Prepares the test case for the getSite() function.
     *
     * @return Site The site object getSite() will return
     */
    protected function expectGetSite()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site ID';
        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($site);
        return $site;
    }
}
