<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class PlanCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PlanCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Plan\ListCommand
     *
     * @group plan
     * @group short
     */
    public function testPlanList()
    {
        $plans = $this->terminusJsonResponse(sprintf('plan:list %s', $this->getSiteName()));
        $this->assertIsArray($plans);
        $this->assertNotEmpty($plans);

        foreach ($plans as $plan) {
            $this->assertIsArray($plan);
            $this->assertNotEmpty($plan);
            $this->assertArrayHasKey('sku', $plan);
            $this->assertArrayHasKey('name', $plan);
            $this->assertArrayHasKey('billing_cycle', $plan);
            $this->assertArrayHasKey('price', $plan);
            $this->assertArrayHasKey('monthly_price', $plan);
        }
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Plan\InfoCommand
     *
     * @group plan
     * @group short
     */
    public function testPlanInfo()
    {
        $plan = $this->terminusJsonResponse(sprintf('plan:info %s', $this->getSiteName()));
        $this->assertIsArray($plan);
        $this->assertNotEmpty($plan);

        $this->assertArrayHasKey('id', $plan);
        $this->assertArrayHasKey('sku', $plan);
        $this->assertNotEmpty($plan['sku']);
        $this->assertArrayHasKey('name', $plan);
        $this->assertNotEmpty($plan['name']);
        $this->assertArrayHasKey('billing_cycle', $plan);
        $this->assertNotEmpty($plan['billing_cycle']);
        $this->assertArrayHasKey('price', $plan);
        $this->assertNotEmpty($plan['price']);
        $this->assertArrayHasKey('monthly_price', $plan);
        $this->assertNotEmpty($plan['monthly_price']);
    }
}
