<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\DotEnvConfig;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

class DotEnvConfigTest extends TerminusTestCase
{
    public function testReadDotEnv()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'terminus_test_');
        unlink($tmp);
        mkdir($tmp);

        file_put_contents($tmp . '/' . '.env', <<<EOT
# Comment here
TERMINUS_SOME_VAR=abc
TERMINUS_ANOTHER_VAR="123"
EOT
        );
        $this->config = new DotEnvConfig($tmp);
        $this->assertEquals('abc', $this->config->get('some_var'));
        $this->assertEquals('123', $this->config->get('another_var'));
        $this->assertEquals($tmp . '/' . '.env', $this->config->getSource('abc'));

        unlink($tmp . '/' . '.env');
        rmdir($tmp);
    }
}
