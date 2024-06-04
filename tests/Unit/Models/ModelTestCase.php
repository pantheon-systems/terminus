<?php

namespace Pantheon\Terminus\Tests\Unit\Models;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Tests\Unit\TerminusTestCase;

/**
 * Class ModelTestCase
 * @package Pantheon\Terminus\UnitTests\Models
 */
abstract class ModelTestCase extends TerminusTestCase
{
    /**
     * @var TerminusCollection
     */
    protected TerminusCollection $collection;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var TerminusModel
     */
    protected TerminusModel $model;
    /**
     * @var Request
     */
    protected $request;

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
    }

    /**
     * Set a mocked config param
     *
     * @param array $values An array of key/values
     */
    protected function configSet($values)
    {
        $this->config->method('get')->will(
            $this->returnCallback(function ($arg) use ($values) {
                return isset($values[$arg]) ? $values[$arg] : null;
            })
        );
    }
}
