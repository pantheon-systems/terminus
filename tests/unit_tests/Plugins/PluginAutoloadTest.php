<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use Pantheon\Terminus\Plugins\PluginAutoloadDependencies;

use League\Container\Container;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Plugins\PluginInfo;
use Psr\Log\NullLogger;

class PluginAutoloadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PluginAutoloadDependencies
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

        $this->autoload = new PluginAutoloadDependencies($this->findTerminusSrcDir());
        $this->autoload->setLogger($this->logger);
    }

    public function testDirectMethodCalls()
    {
        $path = $this->plugins_dir . 'with-autoload/src/Commands/OptionalCommandGroup/NullCommand.php';
        $plugin_dir = $this->callProtected($this->autoload, 'findPluginBaseDir', [$path]);
        $this->assertEquals($this->plugins_dir . 'with-autoload', $plugin_dir);
        $autoload_file = $this->callProtected($this->autoload, 'checkAutoloadPath', [$plugin_dir]);
        $this->assertEquals($this->plugins_dir . 'with-autoload/vendor/autoload.php', $autoload_file);

        $autoload_file_again = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals($this->plugins_dir . 'with-autoload/vendor/autoload.php', $autoload_file_again);
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
            [
                'with-dependencies',
                'src/Commands/OptionalCommandGroup/NullCommand.php',
                'vendor/autoload.php',
            ],
        ];
    }

    /**
     * @dataProvider autoloadTestValues
     */
    public function testAutoload($plugin_path, $commandfile, $expected)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Plugins not supported on Windows yet.");
        }

        $plugin_path = $this->plugins_dir . $plugin_path;
        $path = "$plugin_path/$commandfile";
        if (!empty($expected)) {
            $expected = "$plugin_path/$expected";
        }
        $actual = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test to see what happens when we try to validate a plugin when
     * the Terminus installation folder is missing composer.json &/or composer.lock.
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage Could not load Terminus composer data.
     */
    public function testMissingTerminusComposerData()
    {
        $path = $this->plugins_dir . 'with-dependencies/src/Commands/OptionalCommandGroup/NullCommand.php';
        $misconfigured = new PluginAutoloadDependencies(__DIR__);
        $actual = $this->callProtected($misconfigured, 'findAutoloadFile', [$path]);
        $this->assertEquals("Never reached -- above call will throw.", $actual);
    }

    /**
     * Test to see what happens when we try to validate a plugin that
     * directly requires a component already (indirectly) provided by Terminus.
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The plugin org/conflicting-dependencies-plugin requires the project consolidation/log, which is already provided by Terminus. Please remove this dependency from the plugin by running 'composer remove consolidation/log' in the org/conflicting-dependencies-plugin plugin directory.
     */
    public function testPluginWithConflictingDependency()
    {
        $path = $this->plugins_dir . 'conflicting-dependencies/src/Commands/OptionalCommandGroup/NullCommand.php';
        $actual = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals("Never reached -- above call will throw.", $actual);
    }

    /**
     * Test to see what happens when we try to validate a plugin that
     * indirectly requires a component already provided by Terminus.
     *
     * @expectedException \Pantheon\Terminus\Exceptions\TerminusException
     * @expectedExceptionMessage The plugin org/nested-dependencies-plugin has installed the project consolidation/log: 1.0.0, but Terminus has installed
     */
    public function testPluginWithConflictingNestedDependency()
    {
        $path = $this->plugins_dir . 'nested-dependencies/src/Commands/OptionalCommandGroup/NullCommand.php';
        $actual = $this->callProtected($this->autoload, 'findAutoloadFile', [$path]);
        $this->assertEquals("Never reached -- above call will throw.", $actual);
    }

    public function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }

    /**
     * Determine whether the provided path is inside Terminus itself.
     */
    protected function findTerminusSrcDir()
    {
        // The Terminus class is located at the root of our 'src'
        // directory. Get the path to the class to determine
        // whether or not the path we are testing is inside this
        // same directory.
        $terminus_class = new \ReflectionClass(\Pantheon\Terminus\Terminus::class);
        return dirname($terminus_class->getFileName());
    }
}
