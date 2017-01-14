<?php

namespace Pantheon\Terminus\UnitTests\HTTPS;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Collections\Loadbalancers;
use Pantheon\Terminus\Commands\HTTPS\InfoCommand;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Loadbalancer;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\HTTPS\InfoCommand
 * @package Pantheon\Terminus\UnitTests\HTTPS
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @var Environment
     */
    protected $environment;
    /**
     * @var Loadbalancer
     */
    protected $loadbalancer;
    /**
     * @var Loadbalancers
     */
    protected $loadbalancers;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadbalancers = $this->getMockBuilder(Loadbalancers::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loadbalancer = $this->getMockBuilder(Loadbalancer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects($this->once())
            ->method('getLoadbalancers')
            ->with()
            ->willReturn($this->loadbalancers);
        $this->loadbalancers->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->loadbalancer,]);

        $this->command = new InfoCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the https:info command when HTTPS is active on the given environment
     */
    public function testInfo()
    {
        $ipv4 = 'xxx.xxx.xxx.xxx';
        $ipv6 = 'xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';

        $this->loadbalancer->expects($this->once())
            ->method('isSSL')
            ->with()
            ->willReturn(true);
        $this->loadbalancer->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(['ipv4' => $ipv4, 'ipv6' => $ipv6,]);

        $out = $this->command->info('site.env');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), ['enabled' => 'true', 'ipv4' => $ipv4, 'ipv6' => $ipv6,]);
    }

    /**
     * Tests the https:info command when HTTPS is inactive on the given environment
     */
    public function testInfoInactive()
    {
        $this->loadbalancer->expects($this->once())
            ->method('isSSL')
            ->with()
            ->willReturn(false);

        $out = $this->command->info('site.env');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($out->getArrayCopy(), ['enabled' => 'false', 'ipv4' => null, 'ipv6' => null,]);
    }
}
