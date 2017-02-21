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
    public function setUp()
    {
        parent::setUp();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new Redis(null, ['site' => $this->site,]);
        $this->model->setRequest($this->request);
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

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/settings"),
                $this->equalTo(['method' => 'put', 'form_params' => ['allow_cacheserver' => false,],])
            );
        $out = $this->model->disable();
        $this->assertNull($out);
    }

    /**
     * Tests Redis::enable()
     */
    public function testEnable()
    {
        $this->site->id = 'site_id';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/settings"),
                $this->equalTo(['method' => 'put', 'form_params' => ['allow_cacheserver' => true,],])
            );
        $out = $this->model->enable();
        $this->assertNull($out);
    }
}
