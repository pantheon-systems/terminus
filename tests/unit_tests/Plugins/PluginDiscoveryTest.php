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

    /**
     * Tests the PluginDiscovery::discover() function
     */
    public function testDiscover()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Plugins not supported on Windows yet.");
        }

        $invalid_paths = [
            'invalid-compat-versionless-composer' => 'The composer.json must contain a "compatible-version" field in "extras/terminus"',
            'invalid-composer-json' => 'The file "{path}/composer.json" does not contain valid JSON',
            'invalid-composer-namespace' => 'The namespace "{namespace}" in the composer.json autoload psr-4 section must end with a namespace separator. Should be "{correct}"',
            'invalid-extraless-composer' => 'The composer.json must contain a "terminus" section in "extras"',
            'invalid-no-composer-json' => 'The file "{path}/composer.json" does not exist',
            'invalid-wrong-composer-type' => 'The composer.json must contain a "type" attribute with the value "terminus-plugin"',
        ];
        $valid_paths = [
            'without-namespace',
            'with-namespace',
        ];

        $log = 0;
        foreach ($invalid_paths as $path => $msg) {
            $path = $this->plugins_dir . $path;
            $this->logger->expects($this->at($log++))
                ->method('warning')
                ->with('Plugin Discovery: Ignoring directory {dir} because: {msg}.');
        }

        $pluginList = $this->discovery->discover();
        $actual = $this->composeActualCommandFileDirectories($pluginList);
        $expected = $this->composeExpectedCommandFileDirectories($valid_paths, $this->plugins_dir);
        $reverse_expected = $this->composeExpectedCommandFileDirectories($valid_paths, $this->plugins_dir, true);
        $this->assertTrue(($expected == $actual) || ($reverse_expected == $actual));
    }

    public function testDiscoverFailDirDNE()
    {
        $discovery = new PluginDiscovery(null);
        $this->assertEmpty($discovery->discover());
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

    protected function composeExpectedCommandFileDirectories($valid_paths, $dir, $reverse = false)
    {
        $array = array_map(
            function ($item) use ($dir) {
                return "$dir$item/src";
            },
            $valid_paths
        );
        if ($reverse) {
            $array = array_reverse($array);
        }
        return implode(',', $array);
    }

    protected function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }
}
