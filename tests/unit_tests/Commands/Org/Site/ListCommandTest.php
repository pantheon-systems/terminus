<?php

namespace Pantheon\Terminus\UnitTests\Commands\Org\Site;

use Pantheon\Terminus\Commands\Org\Site\ListCommand;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Org\Site\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Org\Site
 */
class ListCommandTest extends OrgSiteCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sites->method('fetch')
            ->with($this->equalTo(['org_id' => $this->organization->id,]))
            ->willReturn($this->sites);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the org:site:list command when the organization has no sites
     */
    public function testOrgSiteListEmpty()
    {
        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);

        $this->logger->expects($this->once())
            ->method('log')
            ->with($this->equalTo('notice'), $this->equalTo('This organization has no sites.'));

        $out = $this->command->listSites($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([], $out->getArrayCopy());
    }

    /**
     * Tests the org:site:list command
     */
    public function testOrgSiteListNotEmpty()
    {
        $data = [
            'id' => 'site_id',
            'name' => 'Site Name',
        ];

        $this->sites->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['site_id' => $data]);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listSites($this->organization->id);
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals(['site_id' => $data], $out->getArrayCopy());
    }

    /**
     * Tests the org:site:list command
     */
    public function testOrgSiteListByTag()
    {
        $data = ['site_id' => [
          'id' => 'site_id',
          'name' => 'Site Name',
        ]];
        $tag = 'tag';

        $this->sites->expects($this->once())
            ->method('filterByTag')
            ->with($this->equalTo($tag))
            ->willReturn($this->sites);
        $this->sites->expects($this->once())
            ->method('serialize')
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listSites($this->organization->id, compact('tag'));
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
