<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use League\Container\Container;
use Robo\Config;
use Pantheon\Terminus\Request\Request;

/**
 * Class CollectionTestCase
 * @package Pantheon\Terminus\UnitTests\Collections
 */
abstract class CollectionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TerminusCollection
     */
    protected $collection;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Container
     */
    protected $container;

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->method('getConfig')->willReturn($this->getConfig());
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
