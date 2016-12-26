<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Loadbalancers;
use Pantheon\Terminus\Models\Loadbalancer;

/**
 * Class LoadbalancerTest
 * Testing class for Pantheon\Terminus\Models\Loadbalancer
 * @package Pantheon\Terminus\UnitTests\Models
 */
class LoadbalancerTest extends ModelTestCase
{
    /**
     * @var Loadbalancers
     */
    protected $collection;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(Loadbalancers::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests the Loadbalancer::isSSL function when SSL is active
     */
    public function testIsSSL()
    {
        $loadbalancer = new Loadbalancer(
            (object)[
                'cert_string' => 'SOMECERTHERE',
                'ipv4' => 'xxx.xxx.xxx.xxx',
                'ipv6' => 'xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx',
            ],
            ['collection' => $this->collection,]
        );

        $this->assertTrue($loadbalancer->isSSL());
    }

    /**
     * Tests the Loadbalancer::isSSL function when SSL is inactive
     */
    public function testIsSSLAndItIsnt()
    {
        $loadbalancer = new Loadbalancer(
            (object)[
                'ipv4' => 'xxx.xxx.xxx.xxx',
                'ipv6' => 'xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx',
            ],
            ['collection' => $this->collection,]
        );

        $this->assertFalse($loadbalancer->isSSL());
    }

    /**
     * Tests the Loadbalancer::serialize function
     */
    public function testSerialize()
    {
        $lb_data = (object)[
            'cert_string' => 'SOMECERTHERE',
            'ipv4' => 'xxx.xxx.xxx.xxx',
            'ipv6' => 'xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx',
        ];
        $loadbalancer = new Loadbalancer($lb_data, ['collection' => $this->collection,]);

        $out = $loadbalancer->serialize();
        $this->assertEquals($out, ['ipv4' => $lb_data->ipv4, 'ipv6' => $lb_data->ipv6,]);
    }
}
