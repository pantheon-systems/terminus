<?php

namespace Pantheon\Terminus\Tests\Unit\Collections;

use League\Container\Container;
use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Config\TerminusConfig;
use Robo\Config\Config;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Tests\Unit\TerminusTestCase;

/**
 * Class CollectionTestCase
 * @package Pantheon\Terminus\UnitTests\Collections
 */
abstract class CollectionTestCase extends TerminusTestCase
{
    /**
     * @var TerminusCollection
     */
    protected TerminusCollection $collection;
    /**
     * @var TerminusConfig
     */
    protected TerminusConfig $config;
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
    public function getConfig(): TerminusConfig
    {
        return $this->config;
    }

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->config = $this->getMockBuilder(TerminusConfig::class)
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
