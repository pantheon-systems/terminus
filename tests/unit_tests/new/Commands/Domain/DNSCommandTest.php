<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\DNSCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Domain\DNSCommand
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
        $dummy_data = ['domain',];

        $this->hostnames->expects($this->once())
            ->method('setHydration')
            ->with($this->equalTo('recommendations'))
            ->willReturn($this->hostnames);
        $this->hostnames->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->hostname,]);

        $this->hostname->expects($this->once())
            ->method('get')
            ->with($this->equalTo('dns_recommendations'))
            ->willReturn([$dummy_data,]);

        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->getRecommendations("$site_name.{$this->environment->id}");
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([$dummy_data,], $out->getArrayCopy());
    }
}
