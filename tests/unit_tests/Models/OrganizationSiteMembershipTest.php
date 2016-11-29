<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
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
    public function testToString()
    {
        $org = new Organization((object)['id' => '123', 'profile' => (object)['name' => 'My Org']]);
        $org_site = new OrganizationSiteMembership(
            (object)['site' => (object)[], 'tags' => (object)[]],
            ['collection' => (object)['organization' => $org]]
        );
        $this->assertEquals('123: My Org', (string)$org_site);
    }
    
    public function testDelete()
    {
        $site_data = ['site_id' => '123'];
        $container = new Container();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->method('get')->with('id')->willReturn('123');
        $container->add(Site::class, $site);
        $container->add(Tags::class);

        $org = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $wf = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflows ->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_site_membership',
                ['params' => ['site_id' => '123']]
            )
            ->willReturn($wf);
        $org->method('getWorkflows')->willReturn($workflows);

        $org_site = new OrganizationSiteMembership(
            (object)['site' => (object)$site_data, 'tags' => (object)[]],
            ['collection' => (object)['organization' => $org]]
        );
        $org_site->setContainer($container);
        $out = $org_site->delete();
        $this->assertEquals($wf, $out);
    }
}
