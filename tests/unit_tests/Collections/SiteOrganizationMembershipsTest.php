<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteOrganizationMembershipsTest
 * Testing class for Pantheon\Terminus\Collections\SiteOrganizationMemberships
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class SiteOrganizationMembershipsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteOrganizationMemberships
     */
    protected $collection;
    /**
     * @var Organization
     */
    protected $organization;
    /**
     * @var Site
     */
    protected $site;

    /**
     * @var inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->organization = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection = new SiteOrganizationMemberships(['site' => $this->site,]);
    }

    public function testCreate()
    {
        $org_name = 'Organization Name';
        $role = 'some role';
        $this->site->id = 'site id';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($workflows);
        $this->organization->expects($this->once())
            ->method('getLabel')
            ->with()
            ->willReturn($org_name);
        $workflows->expects($this->once())
            ->method('create')
            ->with(
                'add_site_organization_membership',
                ['params' => ['organization_name' => $org_name, 'role' => $role,],]
            )
            ->willReturn($workflow);

        $out = $this->collection->create($this->organization, $role);
        $this->assertEquals($out, $workflow);
    }
}
