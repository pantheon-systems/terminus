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
    public function setUp()
    {
        parent::setUp();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new Solr(null, ['site' => $this->site,]);
        $this->model->setRequest($this->request);
    }

    /**
     * Tests Solr::disable()
     */
    public function testDisable()
    {
        $this->site->id = 'site_id';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/settings"),
                $this->equalTo(['method' => 'put', 'form_params' => ['allow_indexserver' => false,],])
            );
        $out = $this->model->disable();
        $this->assertNull($out);
    }

    /**
     * Tests Solr::enable()
     */
    public function testEnable()
    {
        $this->site->id = 'site_id';

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->site->id}/settings"),
                $this->equalTo(['method' => 'put', 'form_params' => ['allow_indexserver' => true,],])
            );
        $out = $this->model->enable();
        $this->assertNull($out);
    }
}
