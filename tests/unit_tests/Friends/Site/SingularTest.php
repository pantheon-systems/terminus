<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use Pantheon\Terminus\Models\Site;

/**
 * Class SingularTest
 * Testing class for Pantheon\Terminus\Friends\SiteTrait & Pantheon\Terminus\Friends\SiteInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class SingularTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SingularDummyClass
     */
    protected $collection;
    /**
     * @var SingularDummyClass
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

        $this->collection = $this->getMockBuilder(SingularDummyClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new SingularDummyClass(null, ['collection' => $this->collection,]);
    }

    /**
     * Tests SiteTrait::__construct(object, array)
     */
    public function testConstruct()
    {
        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($this->site);
        $model = new SingularDummyClass(null, ['collection' => $this->collection,]);
        $this->assertEquals($this->site, $model->getSite());

        $model = new SingularDummyClass(null, ['site' => $this->site,]);
        $this->assertEquals($this->site, $model->getSite());
    }

    /**
     * Tests SiteTrait::getSite()
     */
    public function testGetSite()
    {
        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($this->site);

        $this->assertEquals($this->site, $this->model->getSite());
    }

    /**
     * Tests SiteTrait::setSite()
     */
    public function testSetSite()
    {
        $this->collection->expects($this->never())->method('getSite');

        $this->model->setSite($this->site);
        $this->assertEquals($this->site, $this->model->getSite());
    }
}
