<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\DNSRecords;
use Pantheon\Terminus\Models\DNSRecord;
use Pantheon\Terminus\Models\Domain;

/**
 * Class DNSRecordTest
 * Testing class for Pantheon\Terminus\Models\DNSRecord
 * @package Pantheon\Terminus\UnitTests\Models
 */
class DNSRecordTest extends \PHPUnit_Framework_TestCase
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
     * @var Domain
     */
    protected $domain;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->data = (object)[
            'detected_value' => 'a detected value',
            'status' => 'u mad',
            'status_message' => 'beefs',
            'target_value' => 'some value',
            'type' => 'the type',
        ];
        $this->dns_records = $this->getMockBuilder(DNSRecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->domain = $this->getMockBuilder(Domain::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->domain->id = 'domain.com';

        $this->dns_records->expects($this->once())
            ->method('getDomain')
            ->with()
            ->willReturn($this->domain);

        $this->dns_record = new DNSRecord($this->data, ['collection' => $this->dns_records,]);
    }

    /**
     * Tests the DNSRecord::serialize() function
     */
    public function testSerialize()
    {
        $expected = [
            'detected_value' => 'a detected value',
            'domain' => $this->domain->id,
            'status' => 'u mad',
            'status_message' => 'beefs',
            'type' => 'the type',
            'value' => 'some value',
        ];
        $this->assertEquals($expected, $this->dns_record->serialize());
    }
}
