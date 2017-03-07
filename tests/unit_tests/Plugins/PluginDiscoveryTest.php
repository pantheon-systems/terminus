<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use League\Container\Container;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Plugins\PluginInfo;
use Psr\Log\NullLogger;

class PluginDiscoveryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var PluginDiscovery
     */
    protected $discovery;
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

        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(['warning',])
            ->getMock();
        $this->plugins_dir = dirname(dirname(__DIR__)) . '/fixtures/plugins/';

        $this->discovery = new PluginDiscovery($this->plugins_dir);
        $this->discovery->setLogger($this->logger);
    }

    public function testDiscover()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Plugins not supported on Windows yet.");
        }

        $invalid_paths = [
            'invalid-no-composer-json' => 'The file "{path}/composer.json" does not exist',
            'invalid-wrong-composer-type' => 'The composer.json must contain a "type" attribute with the value "terminus-plugin"',
        ];
        $valid_paths = [
            'with-namespace',
            'without-namespace',
        ];

        $expected = [];
        $log = 0;
        foreach ($invalid_paths as $path => $msg) {
            $path = $this->plugins_dir . $path;
            $msg = str_replace('{path}', $path, $msg);
            $this->logger->expects($this->at($log++))
                ->method('warning')
                ->with(
                    'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                    ['dir' => $path, 'msg' => $msg,]
                );
        }

        $pluginList = $this->discovery->discover();
        $actual = $this->composeActualCommandFileDirectories($pluginList);
        $expected = $this->composeExpectedCommandFileDirectories($valid_paths, $this->plugins_dir);
        $this->assertEquals($expected, $actual);
    }

    protected function composeActualCommandFileDirectories($pluginList)
    {
        $dirList = [];
        foreach ($pluginList as $plugin) {
            $commandFileDirectory = $this->callProtected($plugin, 'getCommandFileDirectory');
            $dirList[] = $commandFileDirectory;
        }
        $actual = implode(',', $dirList);
        return $actual;
    }

    protected function composeExpectedCommandFileDirectories($valid_paths, $dir)
    {
        return implode(',', array_map(
            function ($item) use ($dir) {
                return "$dir$item/src";
            },
            $valid_paths
        ));
    }

    protected function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }
}
