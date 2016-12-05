<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\DefaultsConfig;

/**
 * Class ConfigTest
 * Testing class for Pantheon\Terminus\Config
 * @package Pantheon\Terminus\UnitTests
 */
class DefaultsConfigTest extends \PHPUnit_Framework_TestCase
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
        $this->config = new DefaultsConfig();
    }

    /**
     * Tests the getHomeDir function
     */
    public function testGetHomeDir()
    {
        // This test doesn't test across all platforms.
        if (getenv('HOME')) {
            $this->assertEquals(getenv('HOME'), $this->config->get('user_home'));
        }
    }

    public function testGetPhpAndOSInfo()
    {
        $this->assertEquals(PHP_VERSION, $this->config->get('php_version'));
        $this->assertEquals(get_cfg_var('cfg_file_path'), $this->config->get('php_ini'));
        $this->assertEquals(PHP_BINARY, $this->config->get('php'));
        $this->assertEquals(php_uname('v'), $this->config->get('os_version'));
    }

    public function testGetTerminusRoot()
    {
        $this->assertEquals(getcwd(), $this->config->get('root'));
    }
}
