<?php

use Terminus\Runner;

/**
 * Testing class for Terminus\Configurator
 */
class ConfiguratorTest extends PHPUnit_Framework_TestCase {

  public function testDefineConstants() {
    unset($_SERVER['Terminus']);
    unset($_SERVER['TERMINUS_VERSION']);
    unset($_SERVER['TERMINUS_PROTOCOL']);
    unset($_SERVER['TERMINUS_HOST']);
    unset($_SERVER['TERMINUS_PORT']);
    unset($_SERVER['TERMINUS_TIME_ZONE']);
    unset($_SERVER['TERMINUS_SCRIPT']);
    $runner = new Runner();
    $this->assertTrue(Terminus);

    $this->assertTrue(defined('TERMINUS_VERSION'));
    $this->assertInternalType('string', TERMINUS_VERSION);

    $this->assertTrue(defined('TERMINUS_PROTOCOL'));
    $this->assertInternalType('string', TERMINUS_PROTOCOL);

    $this->assertTrue(defined('TERMINUS_HOST'));
    $this->assertInternalType('string', TERMINUS_HOST);

    $this->assertTrue(defined('TERMINUS_PORT'));
    $this->assertInternalType('integer', TERMINUS_PORT);

    $this->assertTrue(defined('TERMINUS_TIME_ZONE'));
    $this->assertInternalType('string', TERMINUS_TIME_ZONE);

    $this->assertTrue(defined('TERMINUS_SCRIPT'));
    $this->assertInternalType('string', TERMINUS_SCRIPT);
  }

  public function testImportEnvironmentVariables() {
    $file_name = '.env';
    $this->assertFalse(getenv('TERMINUS_TEST_VAR'));
    setOutputDestination($file_name);
    file_put_contents($file_name, 'TERMINUS_TEST_VAR="ambrosia"');
    $runner = new Runner();
    resetOutputDestination($file_name);
    $this->assertEquals(getenv('TERMINUS_TEST_VAR'), 'ambrosia');
  }

}