<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class DomainsTest
 * Testing class for Pantheon\Terminus\Collections\Domains
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class DomainsTest extends CollectionTestCase
{
    protected $domains;
    protected $environment;
    protected $workflows;

    public function setUp()
    {
        parent::setUp();

        $this->domains = $this->_createDomains();
    }

    protected function _createDomains()
    {
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

        $this->environment->site = (object)['id' => 'abc'];
        $this->environment->id = 'dev';

        $domains = new Domains(['environment' => $this->environment]);
        $domains->setRequest($this->request);
        $domains->setContainer($this->container);
        return $domains;
    }

    public function testCreate()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with('sites/abc/environments/dev/hostnames/dev.example.com', ['method' => 'put']);

        $this->domains->create('dev.example.com');
    }

    public function testSetHydration()
    {
        $this->domains->setHydration('test');
        $this->assertEquals('sites/abc/environments/dev/hostnames?hydrate=test', $this->domains->getUrl());
        $this->domains->setHydration('');
        $this->assertEquals('sites/abc/environments/dev/hostnames?hydrate=', $this->domains->getUrl());
    }
}
