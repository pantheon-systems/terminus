<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Collections\DNSRecords;
use Pantheon\Terminus\Commands\Domain\DNSCommand;
use Pantheon\Terminus\Models\DNSRecord;

/**
 * Class DNSCommandTest
 * Testing class for Pantheon\Terminus\Commands\Domain\DNSCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class DNSCommandTest extends DomainTest
{
    /**
     * @var DNSRecord
     */
    protected $dns_record;
    /**
     * @var DNSRecords
     */
    protected $dns_records;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dns_records = $this->getMockBuilder(DNSRecord::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dns_record = $this->getMockBuilder(DNSRecords::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $expected = [
            'id' => $this->domain->id,
            'detected_value' => 'detected_value',
            'value' => 'target_value',
            'status' => 'status',
            'status_message' => 'status message',
            'type' => 'type',
        ];

        $this->domains->expects($this->once())
            ->method('filter')
            ->willReturn($this->domains);
        $this->domains->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->domain,]);

        $this->domain->expects($this->once())
            ->method('getDNSRecords')
            ->with()
            ->willReturn($this->dns_records);
        $this->dns_records->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($expected);

        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->getRecommendations("$site_name.{$this->environment->id}");
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($expected, $out->getArrayCopy());
    }
}
