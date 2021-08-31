<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Solr;

/**
 * Class SolrTest
 * Testing class for Pantheon\Terminus\Models\Solr
 * @package Pantheon\Terminus\UnitTests\Models
 */
class SolrTest extends ModelTestCase
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
            ->setMethods(['create'])
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->method('getWorkflows')->willReturn($this->workflows);
        $this->model = new Solr(null, ['site' => $this->site,]);
        $this->model->setConfig($this->config);
    }

    /**
     * Tests Solr::disable()
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
                        'addon' => 'indexserver',
                    ],
                ])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->disable();
        $this->assertEquals($workflow, $this->workflow);
    }

    /**
     * Tests Solr::enable()
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
                        'addon' => 'indexserver',
                    ],
                ])
            )
            ->willReturn($this->workflow);

        $workflow = $this->model->enable();
        $this->assertEquals($workflow, $this->workflow);
    }
}
