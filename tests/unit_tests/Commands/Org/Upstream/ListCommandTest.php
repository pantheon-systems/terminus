<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Commands\Org\Upstream\ListCommand;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\UserOrganizationMembership;
use Pantheon\Terminus\UnitTests\Commands\Upstream\UpstreamCommandTest;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Upstream\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Upstream
 */
class ListCommandTest extends UpstreamCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand();
        $this->command->setSession($this->session);
    }

    /**
     * Tests the upstream:list command when filtering by organization
     */
    public function testListOrgUpstreams()
    {
        $filtered = array_values($this->data);
        $expected = [
            $this->data['upstream_id2'],
            $this->data['upstream_id'],
            $this->data['upstream_id4'],
            $this->data['upstream_id3'],
        ];
        $user_org_memberships = $this->getMockBuilder(UserOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_org_membership = $this->getMockBuilder(UserOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org = $this->getMockBuilder(Organization::class)
            ->disableOriginalConstructor()
            ->getMock();
        $org->id = 'orgID';
        $upstreams = $this->getMockBuilder(OrganizationUpstreams::class)
            ->disableOriginalConstructor()
            ->getMock();

        $upstreams->method('getCollectedClass')->willReturn(Upstream::class);
        $this->user->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($user_org_memberships);
        $user_org_memberships->expects($this->once())
            ->method('get')
            ->with($org->id)
            ->willReturn($user_org_membership);
        $user_org_membership->expects($this->once())
            ->method('getOrganization')
            ->with()
            ->willReturn($org);
        $org->expects($this->once())
            ->method('getUpstreams')
            ->with()
            ->willReturn($upstreams);
        $upstreams->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($filtered);

        $out = $this->command->listOrgUpstreams($org->id, ['all' => true,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }
}
