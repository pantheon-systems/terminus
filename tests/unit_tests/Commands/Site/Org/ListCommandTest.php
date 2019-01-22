<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Org;

use Pantheon\Terminus\Commands\Site\Org\ListCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Org\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Org
 */
class ListCommandTest extends CommandTestCase
{
    protected $org_memberships;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->org_memberships->method('getCollectedClass')->willReturn(SiteOrganizationMembership::class);
        $this->site->method('getOrganizationMemberships')->willReturn($this->org_memberships);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests site:org:list
     */
    public function testListOrgs()
    {
        $data = [
            '000' => ['org_name' => 'abc', 'org_id' => '000'],
            '111' => ['org_name' => 'def', 'org_id' => '111'],
        ];

        $this->org_memberships->expects($this->once())
            ->method('serialize')
            ->willReturn($data);

        $out = $this->command->listOrgs('my-site');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }

    /**
     * Tests site:org:list when there aren't any
     */
    public function testListOrgsNone()
    {
        $this->org_memberships->expects($this->once())
            ->method('serialize')
            ->willReturn([]);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('warning'),
                $this->equalTo('This site has no supporting organizations.')
            );

        $this->command->listOrgs('my-site');
    }
}
