<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\DefaultsConfig;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class DefaultsConfigTest
 * Testing class for Pantheon\Terminus\Config\DefaultsConfig
 * @package Pantheon\Terminus\UnitTests\Config
 */
class DefaultsConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = new DefaultsConfig();
    }

    /**
     * Tests the getHomeDir function when using HOME
     */
    public function testGetHomeDirHome()
    {
        $home = getenv('HOME');
        if (!$home) {
            $home = 'home';
            putenv("HOME=$home");
        }
        $config = new DefaultsConfig();
        $this->assertEquals($home, $config->get('user_home'));
    }

    /**
     * Tests the getHomeDir function's functionality when using the HOMEPATH
     */
    public function testGetHomeDirHomepath()
    {
        $homepath = 'homepath';
        putenv("HOME=");
        putenv("HOMEPATH=$homepath");
        putenv("MSYSTEM=''");
        $config = new DefaultsConfig();
        $this->assertEquals($homepath, $config->get('user_home'));
    }

    /**
     * Tests the getHomeDir function's functionality when the system is 'MING'
     */
    public function testGetHomeDirMing()
    {
        putenv("HOME=");
        putenv("HOMEPATH=homepath");
        putenv("MSYSTEM=MING");
        $config = new DefaultsConfig();
        $this->assertEmpty($config->get('user_home'));
    }

    public function testGetPhpAndOSInfo()
    {
        $this->assertEquals(PHP_VERSION, $this->config->get('php_version'));
        $this->assertEquals(get_cfg_var('cfg_file_path'), $this->config->get('php_ini'));
        $this->assertEquals(PHP_BINARY, $this->config->get('php'));
        $this->assertEquals(php_uname('v'), $this->config->get('os_version'));
    }

    public function testGetSourceName()
    {
        $this->assertInternalType('string', $this->config->getSourceName());
    }

    /**
     * Tests the result of getTerminusRoot function
     */
    public function testGetTerminusRoot()
    {
        $this->assertEquals(getcwd(), $this->config->get('root'));
    }

    /**
     * Tests the result of the getTerminusRoot function when Terminus cannot find its root
     */
    public function testGetTerminusRootInvalid()
    {
        $config = new DummyConfigClass();
        $this->setExpectedException(TerminusException::class, 'Could not locate root to set TERMINUS_ROOT.');
        $this->assertNull($config->runGetTerminusRoot('/'));
    }
}
