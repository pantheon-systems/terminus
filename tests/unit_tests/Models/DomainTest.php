<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\DNSRecords;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Domain;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class DomainTest
 * Testing class for Pantheon\Terminus\Models\Domain
 * @package Pantheon\Terminus\UnitTests\Models
 */
class DomainTest extends ModelTestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var Domain
     */
    protected $model;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    public function setUp()
    {
        parent::setUp();

        $this->model = $this->_createDomain(['id' => 'dev.example.com',]);
    }

    protected function _createDomain($attr)
    {
        $collection = $this->getMockBuilder(Domains::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getWorkflows')->willReturn($this->workflows);
        $this->environment->id = 'dev';
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'site id';

        $this->environment->method('getSite')->willReturn($site);
        $collection->method('getEnvironment')->willReturn($this->environment);

        $domain = new Domain((object)$attr, ['collection' => $collection,]);
        $domain->setContainer($this->container);
        $domain->setRequest($this->request);
        return $domain;
    }

    public function testDelete()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("sites/site id/environments/{$this->environment->id}/domains/dev.example.com", ['method' => 'delete',]);
        $this->model->delete();
    }

    /**
     * Tests the Domain::getDNSRecords() function
     */
    public function testGetDNSRecords()
    {
        $data = (object)[
            'id' => 'domain.com',
            'dns_status_details' => (object)[
                'dns_records' => [
                    (object)[
                        'type' => 'platform',
                        'id' => 'live-mysite.pantheonsite.io',
                        'status' => 'status',
                        'status_message' => 'status message',
                        'deletable' => false,
                        'extraneous' => 'info',
                    ]
                ]
            ]
        ];
        $domain = $this->_createDomain($data);

        $records = $this->getMockBuilder(DNSRecords::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->container->expects($this->once())
            ->method('get')
            ->with(
                DNSRecords::class,
                [['data' => $data->dns_status_details->dns_records, 'domain' => $domain,],]
            )
            ->willReturn($records);

        $this->assertEquals($records, $domain->getDNSRecords());
    }
    
    public function testSerialize()
    {
        $data = [
            'type' => 'platform',
            'id' => 'live-mysite.pantheonsite.io',
            'status' => 'status',
            'status_message' => 'status message',
            'deletable' => false,
            'extraneous' => 'info',
        ];
        $expected = [
            'type' => 'platform',
            'id' => 'live-mysite.pantheonsite.io',
            'status' => 'status',
            'status_message' => 'status message',
            'deletable' => 'false',
        ];

        $domain = $this->_createDomain($data);
        $actual = $domain->serialize();
        $this->assertEquals($expected, $actual);
    }
}
