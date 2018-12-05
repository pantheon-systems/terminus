<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Plans;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Plan;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class PlansTest
 * Testing class for Pantheon\Terminus\Collections\Plans
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class PlansTest extends CollectionTestCase
{
    /**
     * @var Plan
     */
    protected $plan;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Workflow
     */
    protected $workflow;
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

        $this->plan = $this->getMockBuilder(Plan::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collection = new Plans(['site' => $this->site,]);
    }

    /**
     * Tests Plans::set()
     */
    public function testSet()
    {
        $sku = 'this-is-a-sku';
        $this->plan->expects($this->once())
            ->method('getSku')
            ->with()
            ->willReturn($sku);
        $this->site->expects($this->once())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->once())
            ->method('create')
            ->with('change_site_service_level', ['params' => compact('sku'),])
            ->willReturn($this->workflow);

        $out = $this->collection->set($this->plan);
        $this->assertEquals($this->workflow, $out);
    }
}
