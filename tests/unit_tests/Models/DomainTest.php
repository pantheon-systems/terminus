<?php

namespace Pantheon\Terminus\UnitTests\Models;

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
     * @var Domain
     */
    protected $model;

    public function setUp()
    {
        parent::setUp();

        $this->model = $this->_createDomain(['id' => 'dev.example.com']);
    }

    protected function _createDomain($attr)
    {
        $collection = $this->getMockBuilder(Domains::class)
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

        $domain->setRequest($this->request);
        return $domain;
    }

    public function testDelete()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("sites/site id/environments/{$this->environment->id}/hostnames/dev.example.com", ['method' => 'delete',]);
        $this->model->delete();
    }
    
    public function testSerialize()
    {
        $data = [
            'dns_zone_name' => 'pantheonsite.io',
            'environment' => 'live',
            'site_id' => '1111-1111-1111-1111-1111',
            'type' => 'platform',
            'id' => 'live-mysite.pantheonsite.io',
            'key' => 'live-mysite.pantheonsite.io',
            'deletable' => false,
        ];
        $domain = $this->_createDomain($data);

        $expected = [
            'domain' => 'live-mysite.pantheonsite.io',
            'dns_zone_name' => 'pantheonsite.io',
            'environment' => 'live',
            'site_id' => '1111-1111-1111-1111-1111',
            'key' =>  'live-mysite.pantheonsite.io',
            'deletable' => false,
        ];
        $actual = $domain->serialize();
        $this->assertEquals($expected, $actual);
    }
}
