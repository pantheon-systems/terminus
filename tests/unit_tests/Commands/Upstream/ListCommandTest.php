<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Commands\Upstream\ListCommand;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\OrganizationUpstream;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\UserOrganizationMembership;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Upstream\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream
 */
class ListCommandTest extends UpstreamCommandTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->upstreams->method('getCollectedClass')
            ->with()
            ->willReturn(Upstream::class);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSession($this->session);
    }

    /**
     * Tests the upstream:list command when using the --all option
     */
    public function testListAllUpstreams()
    {
        $filtered = array_values($this->data);
        $expected = [
            $this->data['upstream_id2'],
            $this->data['upstream_id'],
            $this->data['upstream_id4'],
            $this->data['upstream_id3'],
        ];
        $this->upstreams->expects($this->never())
            ->method('filter');
        $this->upstreams->expects($this->never())
            ->method('filterByName');
        $this->upstreams->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($filtered);

        $out = $this->command->listUpstreams(['all' => true,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }

    /**
     * Tests the upstream:list command with no options
     */
    public function testListUpstreams()
    {
        $filtered = [
            $this->data['upstream_id'],
            $this->data['upstream_id2'],
            $this->data['upstream_id4'],
        ];
        $expected = [
            $this->data['upstream_id4'],
            $this->data['upstream_id2'],
            $this->data['upstream_id'],
        ];
        $this->upstreams->expects($this->once())
            ->method('filter');
        $this->upstreams->expects($this->never())
            ->method('filterByName');
        $this->upstreams->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($filtered);

        $out = $this->command->listUpstreams();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }

    /**
     * Tests the upstream:list command when filtering by organization
     */
    public function testListOrgUpstreams()
    {
        $filtered = [
            $this->data['upstream_id'],
            $this->data['upstream_id2'],
            $this->data['upstream_id4'],
        ];
        $expected = [
            $this->data['upstream_id4'],
            $this->data['upstream_id2'],
            $this->data['upstream_id'],
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
        $upstreams->method('getCollectedClass')
            ->with()
            ->willReturn(OrganizationUpstream::class);

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

        $out = $this->command->listUpstreams(['org' => $org->id,]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }

    /**
     * Tests the upstream:list command when using the framework filter
     */
    public function testListSomeUpstreamsByFramework()
    {
        $filtered = $expected = [
            $this->data['upstream_id3'],
            $this->data['upstream_id4'],
        ];
        $this->upstreams->expects($this->exactly(2))
            ->method('filter');
        $this->upstreams->expects($this->never())
            ->method('filterByName');
        $this->upstreams->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($filtered);

        $out = $this->command->listUpstreams(['framework' => 'drupal',]);
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }

    /**
     * Tests the upstream:list command when using the name filter
     */
    public function testListSomeUpstreamsByName()
    {
        $name = 'Upstream';
        $filtered = [
            $this->data['upstream_id'],
            $this->data['upstream_id2'],
        ];
        $expected = [
            $this->data['upstream_id2'],
            $this->data['upstream_id'],
        ];
        $this->upstreams->expects($this->once())
            ->method('filter');
        $this->upstreams->expects($this->once())
            ->method('filterByName')
            ->with($name);
        $this->upstreams->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($filtered);

        $out = $this->command->listUpstreams(compact('name'));
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }
}
