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
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(SingularDummyClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new SingularDummyClass(null, ['collection' => $this->collection,]);
    }

    /**
     * Tests SiteTrait::getSite()
     */
    public function testGetSite()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getSite')
            ->with()
            ->willReturn($site);

        $this->assertEquals($site, $this->model->getSite());
    }

    /**
     * Tests SiteTrait::setSite()
     */
    public function testSetSite()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->never())->method('getSite');

        $this->model->setSite($site);
        $this->assertEquals($site, $this->model->getSite());
    }
}
