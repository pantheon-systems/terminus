<?php


namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Workflow;

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
        $org = new Organization((object)['id' => '123', 'profile' => (object)['name' => 'My Org']]);
        $org->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $wf = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $org->workflows->expects($this->once())
            ->method('create')
            ->with(
                'remove_organization_site_membership',
                ['params' => ['site_id' => '123']]
            )
            ->willReturn($wf);

        $org_site = new OrganizationSiteMembership(
            (object)['site' => (object)['id' => '123'], 'tags' => (object)[]],
            ['collection' => (object)['organization' => $org]]
        );
        $out = $org_site->delete();
        $this->assertEquals($wf, $out);
    }
}
