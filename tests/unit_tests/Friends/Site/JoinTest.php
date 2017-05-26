<?php

namespace Pantheon\Terminus\UnitTests\Friends\Site;

use League\Container\Container;
use Pantheon\Terminus\Models\Site;

/**
 * Class JoinTest
 * Testing class for Pantheon\Terminus\Friends\SiteJoinTrait & Pantheon\Terminus\Friends\SiteJoinInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Site
 */
class JoinTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;
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

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'site id';

        $this->model = new JoinDummyClass((object)['site' => (object)['id' => $this->site->id,],]);
        $this->model->id = 'model id';
        $this->model->setContainer($this->container);
    }

    /**
     * Tests SiteJoinTrait::*()
     */
    public function testAll()
    {
        $site_references = ['model', 'thing', 'name',];
        $expected = array_merge([$this->model->id,], $site_references);

        $another_site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $another_site->id = $this->model->id;
        $this->container->expects($this->once())
            ->method('get')
            ->with(Site::class, [(object)['id' => $this->site->id,],])
            ->willReturn($another_site);
        $copy_of_another_site = $this->model->getSite();
        $this->assertEquals([$this->model,], $copy_of_another_site->memberships);
        $this->assertEquals($this->model->id, $copy_of_another_site->id);

        $this->site->expects($this->once())
            ->method('getReferences')
            ->with()
            ->willReturn($site_references);

        $this->model->setSite($this->site);
        $this->assertEquals($expected, $this->model->getReferences());
        $this->assertEquals($this->site, $this->model->getSite());
    }
}
