<?php

namespace Pantheon\Terminus\UnitTests\Plugins;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginInfo;
use Pantheon\Terminus\UnitTests\TerminusTestCase;

/**
 * Class PluginInfoTest
 * Testing class for Pantheon\Terminus\Plugins\PluginInfo
 * @package Pantheon\Terminus\UnitTests\Plugins
 */
class PluginInfoTest extends TerminusTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->plugins_dir = __DIR__ . '/../../fixtures/plugins/';

        $this->paths = [
            $this->plugins_dir . 'invalid-no-composer-json',
            $this->plugins_dir . 'invalid-wrong-composer-type',
            $this->plugins_dir . 'with-namespace',
            $this->plugins_dir . 'without-namespace',
            $this->plugins_dir . 'invalid-composer-json',
            $this->plugins_dir . 'invalid-extraless-composer',
            $this->plugins_dir . 'invalid-compat-versionless-composer',
            $this->plugins_dir . 'invalid-composer-namespace',
        ];
    }

    /**
     * Tests the PluginInfo::__construct(string) and PluginInfo::getInfo() functions
     */
    public function testCreatePluginInfo()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $plugin = new PluginInfo($this->paths[2]);

        $info = $plugin->getInfo();
        $this->assertEquals('orgname/with-namespace', $info['name']);
        $this->assertEquals('A test Terminus command with namespacing', $info['description']);
        $this->assertEquals('terminus-plugin', $info['type']);
        $this->assertEquals('MIT', $info['license']);
    }

    /**
     * Tests PluginInfo failing to construct because the composer.json does not contain a compatible version inside its
     * extras' terminus property.
     */
    public function testFailCompatibleVersionlessComposer()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $dir = $this->paths[6];
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('The composer.json must contain a "compatible-version" field in "extras/terminus"');
        new PluginInfo($dir);
    }

    /**
     * Tests PluginInfo failing because the dir doesn't exist
     */
    public function testFailDirDNE()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $dir = '/i/definitely/do/not/exist';
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('The directory "' . $dir . '" does not exist');
        new PluginInfo($dir);
    }

    /**
     * Tests PluginInfo failing to construct because the composer.json does not contain a terminus property in its extras
     */
    public function testFailExtralessComposer()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $dir = $this->paths[5];
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('The composer.json must contain a "terminus" section in "extras"');
        new PluginInfo($dir);
    }

    /**
     * Tests PluginInfo failing to construct due to an invalid composer.json file
     */
    public function testFailInvalidJSON()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $dir = $this->paths[4];
        $this->expectException(TerminusException::class);
        new PluginInfo($dir);
    }

    /**
     * Tests PluginInfo failing because the autoload namespace is invalid
     */
    public function testFailInvalidNamespace()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $dir = $this->paths[7];
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('The namespace "OrgName\\\\PluginName" in the composer.json autoload '
            . 'psr-4 section must end with a namespace separator. Should be "OrgName\\\\PluginName\\\\"');
        new PluginInfo($dir);
    }

    /**
     * Tests PluginInfo failing to construct due to an invalid type
     */
    public function testFailInvalidType()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $this->expectException(TerminusException::class);
        new PluginInfo($this->paths[0]);
    }

    /**
     * Tests PluginInfo failing because the dir is a file
     */
    public function testFailIsFile()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $file = __FILE__;
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('The file "' . $file . '" is not a directory');
        new PluginInfo($file);
    }

    /**
     * Tests PluginInfo failing due to a lack of a plugin dir
     */
    public function testFailNoDir()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('No plugin directory was specified');
        new PluginInfo(false);
    }

    /**
     * Tests PluginInfo failing due to a lack of a composer.json file
     */
    public function testFailNoComposer()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $this->expectException(TerminusException::class);
        new PluginInfo($this->paths[0]);
    }

    /**
     * Tests the PluginInfo::getCompatibleTerminusVersion() function
     */
    public function testGetCompatibleTerminusVersion()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $plugin = new PluginInfo($this->paths[2]);
        $this->assertEquals('1.*', $plugin->getCompatibleTerminusVersion());
    }

    /**
     * Tests the PluginInfo::getName() function
     */
    public function testGetName()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        $plugin = new PluginInfo($this->paths[2]);
        $this->assertEquals('orgname/with-namespace', $plugin->getName());

        $plugin = new PluginInfo($this->paths[3]);
        $this->assertEquals('without-namespace', $plugin->getName());
    }

    /**
     * Tests the loading of commands
     */
    public function testLoadCommands()
    {
        $this->MarkTestSkipped('Plugin manager rewritten in T3, need to re-evaluate plugin tests.');

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestIncomplete('Plugins not supported on Windows yet.');
        }

        $plugin = new PluginInfo($this->paths[2]);

        $expected = [
            $this->plugins_dir . 'with-namespace/src/Commands/NullCommand.php' =>
                'OrgName\\PluginName\\Commands\\NullCommand',
            $this->plugins_dir . 'with-namespace/src/Commands/OptionalCommandGroup/NullCommand.php' =>
                'OrgName\\PluginName\\Commands\\OptionalCommandGroup\\NullCommand',
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
