<?php

namespace Pantheon\Terminus\Tests\Functional;

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

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Plan\ListCommand
     *
     * @group plan
     * @group short
     */
    public function testPlanListCommand()
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
    public function testPlanInfoCommand()
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
        $this->assertArrayHasKey('monthly_price', $plan);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Plan\SetCommand
     *
     * @group plan
     * @group long
     */
    public function testSetPanCommand()
    {
        // Get the current site plan.
        $plan = $this->terminusJsonResponse(sprintf('plan:info %s', $this->getSiteName()));
        $this->assertArrayHasKey('sku', $plan);
        $this->assertNotEmpty($plan['sku']);
        $currentPlanSku = $plan['sku'];

        // Change site plan to "Performance Small".
        $targetPlanSku = 'plan-free-preferred-monthly-1';
        $this->terminus(sprintf('plan:set %s %s', $this->getSiteName(), $targetPlanSku));
        $plan = $this->terminusJsonResponse(sprintf('plan:info %s', $this->getSiteName()));
        $this->assertArrayHasKey('sku', $plan);
        $this->assertNotEmpty($plan['sku']);
        $this->assertEquals($targetPlanSku, $plan['sku']);

        // Change the site plan back.
        $this->terminus(sprintf('plan:set %s %s', $this->getSiteName(), $currentPlanSku));
        $plan = $this->terminusJsonResponse(sprintf('plan:info %s', $this->getSiteName()));
        $this->assertArrayHasKey('sku', $plan);
        $this->assertNotEmpty($plan['sku']);
        $this->assertEquals($currentPlanSku, $plan['sku']);
    }
}
