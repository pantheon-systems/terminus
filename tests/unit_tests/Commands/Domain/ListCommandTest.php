<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Domain\ListCommand;

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
        $dummy_info = ['domain' => 'domain', 'zone' => 'zone',];

        $this->hostnames->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->hostname, $this->hostname,]);
        $this->logger->expects($this->never())
            ->method('log');
        $this->hostname->expects($this->any())
            ->method('serialize')
            ->willReturn($dummy_info);

        $out = $this->command->listDomains('site_name.env_id');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$dummy_info, $dummy_info,], $out->getArrayCopy());
    }
}
