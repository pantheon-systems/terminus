<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Redis;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class RedisTest
 * Testing class for Pantheon\Terminus\Models\Redis
 * @package Pantheon\Terminus\UnitTests\Models
 */
class RedisTest extends ModelTestCase
{
    /**
     * @var Site
     */
    protected $site;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->method('getWorkflows')->willReturn($this->workflows);
        $this->model = new Redis(null, ['site' => $this->site,]);
        $this->model->setRequest($this->request);
        $this->model->setConfig($this->config);
    }

    /**
     * Tests Redis::clear()
     */
    public function testClear()
    {
        $environment = $this->getMockBuilder(Environment::class)
          ->disableOriginalConstructor()
          ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
          ->disableOriginalConstructor()
          ->getMock();
        $workflows = $this->getMockBuilder(Workflows::class)
          ->disableOriginalConstructor()
          ->getMock();
        $environment->method('getWorkflows')->willReturn($workflows);

        $this->site->id = 'site_id';
        $environment->id = 'env_id';

        $workflows->expects($this->once())
            ->method('create')
            ->with('clear_redis_cache')
            ->willReturn($workflow);

        $return_workflow = $this->model->clear($environment);
        $this->assertEquals($workflow, $return_workflow);
    }

    /**
     * Tests Redis::disable()
     */
    public function testDisable()
    {
        $this->site->id = 'site_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('disable_addon'),
                $this->equalTo([
                    'params' => [
                        'addon' => 'cacheserver',
                    ],
                ])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->disable();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Redis::enable()
     */
    public function testEnable()
    {
        $this->site->id = 'site_id';

        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                $this->equalTo('enable_addon'),
                $this->equalTo([
                    'params' => [
                        'addon' => 'cacheserver',
                    ],
                ])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->enable();
        $this->assertEquals($workflow, $this->workflow);
    }
}
