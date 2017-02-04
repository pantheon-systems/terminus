<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use Pantheon\Terminus\Plugins\PluginAutoload;

use League\Container\Container;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Plugins\PluginInfo;
use Psr\Log\NullLogger;

class PluginAutoloadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PluginAutoload
     */
    protected $autoload;
    /**
     * @var NullLogger
     */
    protected $logger;
    /**
     * @var string
     */
    protected $plugins_dir;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(['warning','debug'])
            ->getMock();
        $this->plugins_dir = dirname(dirname(__DIR__)) . '/fixtures/autoload/plugins/';

        $this->autoload = new PluginAutoload($this->plugins_dir);
        $this->autoload->setLogger($this->logger);
    }

    public function testDirectMethodCalls()
    {
        $path = $this->plugins_dir . 'with-autoload/src/Commands/OptionalCommandGroup/NullCommand.php';
        $pluginBaseDir = $this->callProtected($this->autoload, 'findPluginBaseDir', [$path]);
        $this->assertEquals($this->plugins_dir . 'with-autoload', $pluginBaseDir);
        $autoloadFile = $this->callProtected($this->autoload, 'checkAutoloadPath', [$pluginBaseDir]);
        $this->assertEquals($this->plugins_dir . 'with-autoload/vendor/autoload.php', $autoloadFile);

        $autoloadFileAgain = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals($this->plugins_dir . 'with-autoload/vendor/autoload.php', $autoloadFileAgain);
    }

    public static function autoloadTestValues()
    {
        return [
            [
                'invalid-no-composer-json',
                'src/NullCommand.php',
                null,
            ],
            [
                'with-autoload',
                'src/Commands/OptionalCommandGroup/NullCommand.php',
                'vendor/autoload.php',
            ],
        ];
    }

    /**
     * @dataProvider autoloadTestValues
     */
    public function testAutoload($pluginPath, $commandfile, $expected)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Plugins not supported on Windows yet.");
        }

        $pluginPath = $this->plugins_dir . $pluginPath;
        $path = "$pluginPath/$commandfile";
        if (!empty($expected)) {
            $expected = "$pluginPath/$expected";
        }
        $actual = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals($expected, $actual);
    }

    public function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }
}
