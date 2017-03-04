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
            $this->plugins_dir . 'without-namespace',
            $this->plugins_dir . 'invalid-DNE',
            $this->plugins_dir . 'invalid-is-a-file',
            $this->plugins_dir . 'invalid-unreadable',
            $this->plugins_dir . 'invalid-composer-json',
            $this->plugins_dir . 'invalid-no-terminus-extras',
            $this->plugins_dir . 'invalid-no-compatible-version',
            $this->plugins_dir . 'invalid-namespace',
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

    public function testFailDNE()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[4]);
    }

    public function testFailFalsy()
    {
        $this->setExpectedException(TerminusException::class, 'No plugin directory was specified.');
        new PluginInfo(false);
    }

    public function testFailInvalidComposerJson()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[7]);
    }

    public function testFailIsAFile()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[5]);
    }

    public function testFailIsUnreadable()
    {
        chmod($this->paths[6], 000);
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[6]);
        chmod($this->paths[6], 755);
    }

    public function testFailInvalidComposerType()
    {
        $this->setExpectedException(
            TerminusException::class,
            'The composer.json must contain a "type" attribute with the value "terminus-plugin".'
        );
        new PluginInfo($this->paths[1]);
    }

    public function testFailInvalidNamespace()
    {
        $this->setExpectedException(
            TerminusException::class,
            'The namespace "OrgName\\\\PluginName" in the composer.json autoload psr-4 section must end with a namespace separator. Should be "OrgName\\\\PluginName\\\\"'
        );
        new PluginInfo($this->paths[10]);
    }

    public function testFailNoCompatibleVersion()
    {
        $this->setExpectedException(
            TerminusException::class,
            'The composer.json must contain a "compatible-version" field in "extras/terminus"'
        );
        new PluginInfo($this->paths[9]);
    }

    public function testFailNoComposer()
    {
        $this->setExpectedException(TerminusException::class);
        new PluginInfo($this->paths[0]);
    }

    public function testFailNoComposerExtras()
    {
        $this->setExpectedException(
            TerminusException::class,
            'The composer.json must contain a "terminus" section in "extras".'
        );
        new PluginInfo($this->paths[8]);
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

    public function testLoadCommands()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete('Plugins not supported on Windows yet.');
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
}
