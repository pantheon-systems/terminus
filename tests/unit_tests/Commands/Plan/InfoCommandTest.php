<?php

namespace Pantheon\Terminus\UnitTests\Commands\Plan;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Plan\InfoCommand;
use Pantheon\Terminus\Models\Plan;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Plan\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Plan
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @var Plan
     */
    protected $plan;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->plan = $this->getMockBuilder(Plan::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new InfoCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises plan:info
     */
    public function testInfo()
    {
        $data = [
            'billing_cycle' => 'monthly',
            'id' => 'plan id',
            'name' => 'Plan A',
            'price' => 2599,
            'monthly_price' => 2599,
            'sku' => 'Plan.sku',
            'storage' => 1234,
            'support_plan' => 'silver',
        ];

        $this->site->expects($this->once())
            ->method('getPlan')
            ->with()
            ->willReturn($this->plan);
        $this->plan->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->plan);
        $this->plan->expects($this->once())
            ->method('serialize')
            ->willReturn($data);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->info('my-site');
        $this->assertInstanceOf(PropertyList::class, $out);
    }
}
