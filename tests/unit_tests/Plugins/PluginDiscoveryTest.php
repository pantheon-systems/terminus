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
        $this->plugins_dir = __DIR__ . '/../../fixtures/plugins/';

        $this->discovery = new PluginDiscovery($this->plugins_dir);
        $this->discovery->setContainer($this->container);
        $this->discovery->setLogger($this->logger);
    }

    public function testDiscover()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete('Plugins not supported on Windows yet.');
        }

        $unreadable_path = $this->plugins_dir . 'invalid-unreadable';
        chmod($unreadable_path, 000);

        $paths = [
            $this->plugins_dir . 'invalid-composer-json',
            $this->plugins_dir . 'invalid-namespace',
            $this->plugins_dir . 'invalid-no-compatible-version',
            $this->plugins_dir . 'invalid-no-composer-json',
            $this->plugins_dir . 'invalid-no-terminus-extras',
            $this->plugins_dir . 'invalid-wrong-composer-type',
            $this->plugins_dir . 'with-namespace',
            $this->plugins_dir . 'without-namespace',
        ];

        $expected = [];
        $log = 0;
        foreach ($paths as $i => $path) {
            if (strpos($path, 'invalid')) {
                $msg = "Plugin $i is not valid";
                $this->container->expects($this->at($i))
                    ->method('get')
                    ->with(PluginInfo::class, [$path,])
                    ->willThrowException(new TerminusException($msg));

                $this->logger->expects($this->at($log++))
                    ->method('warning')
                    ->with(
                        'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                        ['dir' => $path, 'msg' => $msg,]
                    );
            } else {
                $plugin = $this->getMockBuilder(PluginInfo::class)
                    ->disableOriginalConstructor()
                    ->getMock();
                $this->container->expects($this->at($i))
                    ->method('get')
                    ->with(PluginInfo::class, [$path,])
                    ->willReturn($plugin);
                $expected[] = $plugin;
            }
        }

        $actual = $this->discovery->discover();
        //$this->assertEquals($expected, $actual);

        chmod($unreadable_path, 777);
    }
}
