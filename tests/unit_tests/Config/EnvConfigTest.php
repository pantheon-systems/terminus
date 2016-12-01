<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\EnvConfig;

class EnvConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testReadEnv()
    {
        $_ENV['TERMINUS_SOME_VAR'] = 'abc';
        $_ENV['TERMINUS_ANOTHER_VAR'] = '123';
        $_ENV['NOT_RELATED'] = '123';
        $this->config = new EnvConfig();
        $this->assertEquals('abc', $this->config->get('some_var'));
        $this->assertEquals('123', $this->config->get('another_var'));
        $this->assertEmpty($this->config->get('NOT_RELATED'));
        $this->assertEquals('Environment Variable', $this->config->getSource('Environment Variable'));
    }
}
