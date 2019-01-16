<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Plans;
use Pantheon\Terminus\Models\Plan;

/**
 * Class PlanTest
 * Testing class for Pantheon\Terminus\Models\Plan
 * @package Pantheon\Terminus\UnitTests\Models
 */
class PlanTest extends ModelTestCase
{
    /**
     * array
     */
    protected $data;
    /**
     * @var Plans
     */
    protected $plans;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->plans = $this->getMockBuilder(Plans::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->data = [
            'monthly_collection' => [
                'attributes' => [
                    'billing_cycle' => 'monthly',
                    'id' => 'monthly_id_collection',
                    'plan_name' => 'Monthly Plan Name (from collection)',
                    'plan_sku' => 'Monthly Plan SKU (from collection)',
                    'price' => 100,
                ],
            ],
            'annual_collection' => [
                'attributes' => [
                    'billing_cycle' => 'annual',
                    'id' => 'annual_id_collection',
                    'plan_name' => 'Annual Plan Name (from collection)',
                    'plan_sku' => 'Annual Plan SKU (from collection)',
                    'price' => 2400,
                ],
            ],
            'monthly_site' => [
                'billing_cycle' => 'monthly',
                'id' => 'monthly_id_site',
                'name' => 'Monthly Plan Name (from site)',
                'price' => 300,
                'sku' => 'Monthly Plan SKU (from site)',
            ],
            'annual_site' => [
                'billing_cycle' => 'annual',
                'id' => 'annual_id_site',
                'name' => 'Annual Plan Name (from site)',
                'price' => 4800,
                'sku' => 'Annual Plan SKU (from site)',
            ],
            'free' => [
                'billing_cycle' => 'monthly',
                'id' => 'plan_no-cost',
                'name' => 'Free Plan',
                'price' => 0,
                'sku' => 'plan-free_is-no-cost',
            ],
        ];
    }

    /**
     * Tests Plan::__construct() to ensure the variability of the plan data does not affect operation of the model
     */
    public function testConstruct()
    {
        $data_from_collection = $this->data['monthly_collection'];
        $this->assertEquals(
            $data_from_collection['attributes']['plan_name'],
            $this->makePlan($data_from_collection)->get('plan_name')
        );

        $data_from_site = $this->data['monthly_site'];
        $this->assertEquals($data_from_site['name'], $this->makePlan($data_from_site)->get('name'));
    }

    /**
     * Tests Plan::getMonthlyPrice()
     */
    public function testGetMonthlyPrice()
    {
        $annual_data = $this->data['annual_collection'];
        $this->assertEquals(
            ($annual_data['attributes']['price']/12),
            $this->makePlan($annual_data)->getMonthlyPrice()
        );

        $monthly_data = $this->data['monthly_collection'];
        $this->assertEquals($monthly_data['attributes']['price'], $this->makePlan($monthly_data)->getMonthlyPrice());
    }

    /**
     * Tests Plan::getName() to ensure the variability of the plan data does not affect operation of the model
     */
    public function testGetName()
    {
        $data_from_collection = $this->data['annual_collection'];
        $this->assertEquals(
            $data_from_collection['attributes']['plan_name'],
            $this->makePlan($data_from_collection)->getName()
        );

        $data_from_site = $this->data['annual_site'];
        $this->assertEquals($data_from_site['name'], $this->makePlan($data_from_site)->getName());
    }

    /**
     * Tests Plan::getReferences()
     */
    public function testGetReferences()
    {
        $data = $this->data['monthly_site'];
        $this->assertEquals(
            [$data['id'], $data['sku'],],
            $this->makePlan($data)->getReferences()
        );
    }

    /**
     * Tests Plan::getSku() to ensure the variability of the plan data does not affect operation of the model
     */
    public function testGetSku()
    {
        $data_from_collection = $this->data['annual_collection'];
        $this->assertEquals(
            $data_from_collection['attributes']['plan_sku'],
            $this->makePlan($data_from_collection)->getSku()
        );

        $data_from_site = $this->data['annual_site'];
        $this->assertEquals($data_from_site['sku'], $this->makePlan($data_from_site)->getSku());
    }

    /**
     * Tests Plan::isAnnual()
     */
    public function testIsAnnual()
    {
        $this->assertTrue($this->makePlan($this->data['annual_collection'])->isAnnual());
        $this->assertFalse($this->makePlan($this->data['monthly_collection'])->isAnnual());
    }

    /**
     * Tests Plan::isFree()
     */
    public function testIsFree()
    {
        $this->assertFalse($this->makePlan($this->data['annual_collection'])->isFree());
        $this->assertTrue($this->makePlan($this->data['free'])->isFree());
    }

    /**
     * Tests Plan::isMonthly()
     */
    public function testIsMonthly()
    {
        $this->assertFalse($this->makePlan($this->data['annual_collection'])->isMonthly());
        $this->assertTrue($this->makePlan($this->data['monthly_collection'])->isMonthly());
    }

    /**
     * Tests Plan::serialize()
     */
    public function testSerialize()
    {
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->with('monetary_format')
            ->willReturn('$%01.2f');
        $data = $this->data['monthly_site'];
        $formatted_price = '$3.00';
        $expected = [
            'billing_cycle' => $data['billing_cycle'],
            'id' => $data['id'],
            'monthly_price' => $formatted_price,
            'name' => $data['name'],
            'price' => $formatted_price,
            'sku' => $data['sku'],
        ];
        $this->assertEquals(
            $expected,
            $this->makePlan($data)->serialize()
        );
    }

    /**
     * @param array $attributes
     * @return Plan
     */
    protected function makePlan(array $attributes = [])
    {
        $model = new Plan((object)$attributes, ['collection' => $this->plans,]);
        $model->setConfig($this->config);
        $model->setRequest($this->request);
        return $model;
    }
}
