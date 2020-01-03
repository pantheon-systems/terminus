<?php

namespace Pantheon\Terminus\UnitTests\Collections;

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
    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->request = $this->createMock(Request::class);
        $this->request->method('getConfig')->willReturn($this->getConfig());
        $this->container = $this->createMock(Container::class);
    }
}
