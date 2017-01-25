<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginInfo;

class PluginInfoTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->plugins_dir = __DIR__ . '/../../fixtures/plugins/';

        $this->paths = [
            $this->plugins_dir . 'invalid-no-composer-json',
            $this->plugins_dir . 'invalid-wrong-composer-type',
            $this->plugins_dir . 'with-namespace',
            $this->plugins_dir . 'without-namespace'
        ];
    }

    public function testCreatePluginInfo()
    {
        $plugin = new PluginInfo($this->paths[2]);

        $info = $plugin->getInfo();
        $this->assertEquals('orgname/with-namespace', $info['name']);
        $this->assertEquals('A test Terminus command with namespacing', $info['description']);
        $this->assertEquals('terminus-plugin', $info['type']);
        $this->assertEquals('MIT', $info['license']);
    }

    public function testLoadCommands()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete("Plugins not supported on Windows yet.");
        }

        $plugin = new PluginInfo($this->paths[2]);

        $expected = [
            $this->plugins_dir . 'with-namespace/src/Commands/NullCommand.php' => 'OrgName\\PluginName\\Commands\\NullCommand',
            $this->plugins_dir . 'with-namespace/src/Commands/OptionalCommandGroup/NullCommand.php' => 'OrgName\\PluginName\\Commands\\OptionalCommandGroup\\NullCommand',
        ];
        $actual = $plugin->getCommandsAndHooks();
        $this->assertEquals($expected, $actual);


        $plugin = new PluginInfo($this->paths[3]);

        $expected = [
            $this->plugins_dir . 'without-namespace/src/NullCommand.php' => 'NullCommand',
        ];
        $actual = $plugin->getCommandsAndHooks();
        $this->assertEquals($expected, $actual);
    }

    public function testFailNoComposer()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[0]);
    }

    public function testFailInvalidType()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[0]);
    }

    public function testGetName()
    {
        $plugin = new PluginInfo($this->paths[2]);
        $this->assertEquals('orgname/with-namespace', $plugin->getName());

        $plugin = new PluginInfo($this->paths[3]);
        $this->assertEquals('without-namespace', $plugin->getName());
    }

    public function testGetTerminusVersion()
    {
        $plugin = new PluginInfo($this->paths[2]);
        $this->assertEquals('1.*', $plugin->getCompatibleTerminusVersion());
    }
}
