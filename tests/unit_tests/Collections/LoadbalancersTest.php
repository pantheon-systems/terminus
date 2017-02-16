<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Loadbalancers;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Loadbalancer;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class LoadbalancersTest
 * Testing class for Pantheon\Terminus\Collections\Loadbalancers
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class LoadbalancersTest extends CollectionTestCase
{
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var Loadbalancer
     */
    protected $loadbalancers;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->loadbalancers = $this->createLoadbalancers();
    }

    /**
     * Indirectly tests the Loadbalancers::getCollectionData(array) function
     */
    public function testGetCollectionData()
    {
        $environment_data = [
            'data' => (object)[
                'loadbalancers' => (object)[
                    'long_UUID' => (object)['environment' => $this->environment->id,],
                    'another_long_UUID' => (object)['environment' => 'env_id',],
                ]
            ]
        ];

        $this->request->expects($this->once())
            ->method('request')
            ->willReturn($environment_data);

        $loadbalancers = $this->loadbalancers->all();
        $this->assertInternalType('array', $loadbalancers);
        $this->assertEquals(1, count($loadbalancers));
    }

    /**
     * Tests the Loadbalancers::getUrl function
     */
    public function testGetUrl()
    {
        $env_fetch_string = 'https://some.url';

        $this->environment->expects($this->once())
            ->method('getUrl')
            ->with()
            ->willReturn($env_fetch_string);

        $out = $this->loadbalancers->getUrl();
        $this->assertEquals($out, "$env_fetch_string?environment_state=true");
    }

    /**
     * Creates and sets up a loadbalancer object with a mock Environment
     *
     * @return Loadbalancers
     */
    protected function createLoadbalancers()
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
        $this->environment->id = 'dev';
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getSite')->willReturn($site);

        $loadbalancers = new Loadbalancers(['environment' => $this->environment,]);
        $loadbalancers->setRequest($this->request);
        $loadbalancers->setContainer($this->container);
        return $loadbalancers;
    }
}
