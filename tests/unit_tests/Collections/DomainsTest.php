<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class DomainsTest
 * Testing class for Pantheon\Terminus\Collections\Domains
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class DomainsTest extends CollectionTestCase
{
    /**
     * @var Domains
     */
    protected $collection;
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Workflows
     */
    protected $workflows;

    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->createDomains();
    }

    public function testCreate()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("sites/{$this->site->id}/environments/{$this->environment->id}/hostnames/dev.example.com", ['method' => 'put']);

        $this->collection->create('dev.example.com');
    }

    public function testSetHydration()
    {
        $this->collection->setHydration('test');
        $this->assertEquals("sites/{$this->site->id}/environments/{$this->environment->id}/hostnames?hydrate=test", $this->collection->getUrl());
        $this->collection->setHydration('');
        $this->assertEquals("sites/{$this->site->id}/environments/{$this->environment->id}/hostnames?hydrate=", $this->collection->getUrl());
    }

    protected function createDomains()
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
        $this->environment->id = 'dev';
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site id';

        $this->environment->method('getSite')->willReturn($this->site);

        $domains = new Domains(['environment' => $this->environment,]);
        $domains->setRequest($this->request);
        $domains->setContainer($this->container);
        return $domains;
    }
}
