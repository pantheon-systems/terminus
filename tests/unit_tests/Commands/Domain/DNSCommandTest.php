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
     * Tests the domain:dns command
     */
    public function testDNS()
    {
        $site_name = 'site_name';
        $this->environment->id = 'env_id';
        $this->domain->id = 'domain_id';
        $dummy_data = (object)[
            'dns_records' => [
                (object)[
                    'detected_value' => 'detected value',
                    'status' => 'status',
                    'target_value' => 'value',
                    'type' => 'type',
                ],
            ]
        ];
        $expected = [
            'name' => $this->domain->id,
            'detected_value' => $dummy_data->dns_records[0]->detected_value,
            'status' => $dummy_data->dns_records[0]->status,
            'value' => $dummy_data->dns_records[0]->target_value,
            'type' => $dummy_data->dns_records[0]->type,
        ];

        $this->domains->expects($this->once())
            ->method('filter')
            ->willReturn($this->domains);
        $this->domains->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->domain,]);

        $this->domain->expects($this->once())
            ->method('get')
            ->with($this->equalTo('dns_status_details'))
            ->willReturn($dummy_data);

        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->getRecommendations("$site_name.{$this->environment->id}");
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals([$expected,], $out->getArrayCopy());
    }
}
