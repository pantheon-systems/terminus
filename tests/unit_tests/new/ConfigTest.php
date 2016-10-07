<?php

namespace Pantheon\Terminus\UnitTests;

use Pantheon\Terminus\Config;

/**
 * Testing class for Pantheon\Terminus\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Creates oft-used objects
     */
    public function __construct($name = null, array $data = [], $dataName = null)
    {
        parent::__construct($name, $data, $dataName);
        $this->config = new Config();
    }

    /**
     * Tests the get function
     *
     * @expectedException \Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage No configuration setting for DNE found.
     */
    public function testGet()
    {
        $this->assertTrue((boolean)$this->config->get('test_mode'));
        $this->config->get('DNE');
    }

    /**
     * Tests the getHomeDir function
     */
    public function testGetHomeDir()
    {
        $this->assertEquals(getenv('HOME'), $this->config->getHomeDir());
    }
}
