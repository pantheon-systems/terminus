<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use League\Container\Container;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Plugins\PluginInfo;
use Psr\Log\NullLogger;

class PluginDiscoveryTest extends \PHPUnit_Framework_TestCase
{
    public function testDiscover()
    {
        $plugins_dir = __DIR__ . '/../../fixtures/plugins/';

        $paths = [
            $plugins_dir  . 'invalid-no-composer-json',
            $plugins_dir  . 'invalid-wrong-composer-type',
            $plugins_dir  . 'with-namespace',
            $plugins_dir  . 'without-namespace'
        ];

        $logger = $this->getMockBuilder(NullLogger::class)
            ->setMethods(array('warning'))
            ->getMock();


        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();

        $expected = [];
        $log = 0;
        foreach ($paths as $i => $path) {
            if (strpos($path, 'invalid')) {
                $msg = "Plugin $i is not valid";
                $container->expects($this->at($i))
                    ->method('get')
                    ->with(PluginInfo::class, [$path])
                    ->willThrowException(new TerminusException($msg));

                $logger->expects($this->at($log++))
                    ->method('warning')
                    ->with('Plugin Discovery: Ignoring directory {dir} because: {msg}.', ['dir' => $path, 'msg' => $msg]);
            } else {
                $plugin = $this->getMockBuilder(PluginInfo::class)
                    ->disableOriginalConstructor()
                    ->getMock();
                $container->expects($this->at($i))
                    ->method('get')
                    ->with(PluginInfo::class, [$path])
                    ->willReturn($plugin);
                $expected[] = $plugin;
            }
        }

        $discovery = new PluginDiscovery($plugins_dir);
        $discovery->setContainer($container);
        $discovery->setLogger($logger);

        $actual = $discovery->discover();
        $this->assertEquals($expected, $actual);
    }
}
