<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\YamlConfig;

class YamlConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    public function testReadYaml()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'terminus_test_');
        file_put_contents($tmp, <<<EOT
TERMINUS_SOME_VAR: abc
TERMINUS_ANOTHER_VAR: 123
EOT
        );
        $this->config = new YamlConfig($tmp);
        $this->assertEquals('abc', $this->config->get('some_var'));
        $this->assertEquals('123', $this->config->get('another_var'));
        $this->assertEquals($tmp, $this->config->getSource('abc'));

        unlink($tmp);
    }
}
