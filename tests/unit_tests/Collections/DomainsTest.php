<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Domain;
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

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->id = 'dev';
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site id';
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->method('getSite')
            ->willReturn($this->site);
        $this->environment->method('getWorkflows')
            ->willReturn($this->workflows);

        $this->collection = new Domains(['environment' => $this->environment,]);
        $this->collection->setRequest($this->request);
        $this->collection->setContainer($this->container);
    }

    /**
     * Tests the Domains::create() function
     */
    public function testCreate()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with("sites/{$this->site->id}/environments/{$this->environment->id}/domains/dev.example.com", ['method' => 'put']);

        $this->collection->create('dev.example.com');
    }

    /**
     * Tests the Domains::has(string) function
     */
    public function testHas()
    {
        $data = [
            'foo.net' => (object)[],
            'bar.org' => (object)[],
        ];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/environments/{$this->environment->id}/domains"),
                $this->equalTo(['options' => ['method' => 'get',],])
            )
            ->willReturn(compact('data'));
        $i = 0;
        foreach ($data as $domain_str => $obj) {
            $domain = $this->getMockBuilder(Domain::class)
                ->disableOriginalConstructor()
                ->getMock();
            $domain->id = $domain_str;
            $domain->method('getReferences')->willReturn([$domain_str,]);
            $this->container->expects($this->at($i))
                ->method('get')
                ->with(Domain::class, [$obj, ['id' => $domain_str, 'collection' => $this->collection,],])
                ->willReturn($domain);
            $i++;
        }

        $this->assertTrue($this->collection->has('foo.net'));
        $this->assertFalse($this->collection->has('hello.world'));
    }
}
