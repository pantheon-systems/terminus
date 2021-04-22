<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Domain\ListCommand;
use Pantheon\Terminus\Models\Domain;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class ListCommandTest extends DomainTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the domain:list command
     */
    public function testList()
    {
        $dummy_info = ['123' => ['domain' => 'domain', 'zone' => 'zone']];

        $this->domains->method('getCollectedClass')->willReturn(Domain::class);
        $this->domains->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($dummy_info);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->listDomains('site_name.env_id');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($dummy_info, $out->getArrayCopy());
    }
}
