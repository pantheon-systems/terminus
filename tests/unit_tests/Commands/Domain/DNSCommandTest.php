<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\DNSCommand;

/**
 * Class DNSCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\DNSCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class DNSCommandTest extends DomainTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new DNSCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the domain:remove command
     */
    public function testDNS()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $this->domain->id = 'domain_id';
        $dummy_data = ['value' => 'value', 'type' => 'type',];

        $this->domains->expects($this->once())
            ->method('setHydration')
            ->with($this->equalTo('recommendations'))
            ->willReturn($this->domains);
        $this->domains->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->domain,]);

        $this->domain->expects($this->once())
            ->method('get')
            ->with($this->equalTo('dns_recommendations'))
            ->willReturn([(object)$dummy_data,]);

        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->getRecommendations("$site_name.{$this->environment->id}");
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([array_merge(['name' => $this->domain->id,], $dummy_data),], $out->getArrayCopy());
    }
}
