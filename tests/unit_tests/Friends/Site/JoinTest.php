<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Models\Site;

/**
 * Class JoinTest
 * Testing class for Pantheon\Terminus\Friends\SiteJoinTrait & Pantheon\Terminus\Friends\SiteJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class JoinTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JoinDummyClass
     */
    protected $model;
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

        $this->model = new JoinDummyClass();
        $this->model->id = 'model id';
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Tests SiteJoinTrait::*()
     */
    public function testAll()
    {
        $site_references = ['model', 'thing', 'name',];
        $expected = array_merge([$this->model->id,], $site_references);

        $this->site->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($site_references);

        $this->model->setSite($this->site);
        $this->assertEquals($expected, $this->model->getReferences());
        $this->assertEquals($this->site, $this->model->getSite());
    }
}
