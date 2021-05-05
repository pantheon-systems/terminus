<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Consolidation\Config\Util\ConfigOverlay;
use League\Container\Container;
use Robo\Config;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

/**
 * Class CollectionTestCase
 * @package Pantheon\Terminus\UnitTests\Collections
 */
abstract class CollectionTestCase extends TerminusTestCase
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
    public function setUp(): void
    {
        $this->config = $this->getMockBuilder(ConfigOverlay::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request
            ->method('getConfig')
            ->willReturn($this->getConfig());
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
