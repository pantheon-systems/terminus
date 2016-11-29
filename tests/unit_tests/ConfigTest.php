<?php

namespace Pantheon\Terminus\UnitTests;

use Pantheon\Terminus\Config;

/**
 * Class ConfigTest
 * Testing class for Pantheon\Terminus\Config
 * @package Pantheon\Terminus\UnitTests
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
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
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

    public function testGetPhpAndOSInfo()
    {
        $this->assertEquals(PHP_VERSION, $this->config->get('php_version'));
        $this->assertEquals(get_cfg_var('cfg_file_path'), $this->config->get('php_ini'));
        $this->assertEquals(PHP_BINARY, $this->config->get('php'));
        $this->assertEquals(php_uname('v'), $this->config->get('os_version'));
    }
}
