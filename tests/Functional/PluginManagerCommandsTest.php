<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManagerCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PluginManagerCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\ListCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\ReloadCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\InstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\UpdateCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\UninstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\SearchCommand
     *
     * @group plugins
     * @group long
     */
    public function testPluginsCommands()
    {
        $filesystem = new Filesystem();
        $pluginsDir = $this->getPluginsDir();
        $dependenciesBaseDir = $this->getDependenciesBaseDir();
        $filesystem->remove([$pluginsDir, $dependenciesBaseDir]);

        // List commands to check that plugin commands are not available.
        $helloCommand = 'hello';
        $this->assertCommandNotExists($helloCommand);
        $this->assertNoPlugins();

        // Search plugin.
        $pluginName = 'terminus-plugin-example';
        $pluginPackage = 'pantheon-systems/terminus-plugin-example';
        $pluginList = $this->terminusJsonResponse(sprintf('self:plugin:search %s', $pluginName));
        $this->assertIsArray($pluginList);
        $this->assertNotEmpty($pluginList);
        $plugin = reset($pluginList);
        $this->assertIsArray($plugin);
        $this->assertArrayHasKey('name', $plugin);
        $this->assertStringContainsString(
            $pluginPackage,
            $plugin['name'],
            sprintf('Plugin search result should contain %s plugin.', $pluginPackage)
        );

        // Install plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:install %s', $pluginPackage));
        $this->assertStringContainsString(
            sprintf('Installed %s', $pluginPackage),
            $pluginList,
            'Terminus plugin installation failed.'
        );
        $this->assertPluginExists($pluginName);
        $this->assertCommandExists($helloCommand);

        // Try updating plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:update %s', $pluginPackage));
        $this->assertStringContainsString(
            'Nothing to install, update or remove',
            $pluginList,
            'Terminus plugin update failed.'
        );
        $this->assertPluginExists($pluginName);
        $this->assertCommandExists($helloCommand);

        // Try reloading plugins.
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:reload');
        $this->assertStringContainsString('Plugins reload done', $pluginList, 'Terminus plugin reload failed.');
        $this->assertPluginExists($pluginName);
        $this->assertCommandExists($helloCommand);

        // Try uninstalling plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:uninstall %s', $pluginPackage));
        $this->assertStringContainsString(
            sprintf('Uninstalled %s', $pluginPackage),
            $pluginList,
            'Terminus plugin uninstall failed.'
        );
        $this->assertNoPlugins();
        $this->assertCommandNotExists($helloCommand);

        // Create new plugin.
        $tempPluginFile = $filesystem->tempnam(sys_get_temp_dir(), 'terminustest');
        if ($filesystem->exists($tempPluginFile)) {
            $filesystem->remove($tempPluginFile);
        }
        $pluginList = $this->terminusWithStderrRedirected(
            sprintf('self:plugin:create %s --project-name=terminus-test/newplugin', $tempPluginFile)
        );
        $this->assertStringContainsString(
            'Installed terminus-test/newplugin:@dev',
            $pluginList,
            'Terminus plugin creation failed.'
        );
        $this->assertCommandExists($helloCommand);

        // Uninstall recently created plugin.
        $pluginName = 'terminus-test/newplugin';
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:uninstall %s', $pluginName));
        $this->assertStringContainsString(
            sprintf('Uninstalled %s', $pluginName),
            $pluginList,
            'Terminus plugin uninstall failed.'
        );

        // Cleanup folder.
        $filesystem->remove($tempPluginFile);
    }

    /**
     * Asserts the plugin exists.
     *
     * @param string $pluginName
     *   The plugin name to assert.
     */
    protected function assertPluginExists(string $pluginName)
    {
        $pluginList = $this->terminusJsonResponse('self:plugin:list');
        $this->assertIsArray($pluginList);
        $this->assertNotEmpty($pluginList);

        $plugin = reset($pluginList);
        $this->assertIsArray($plugin);
        $this->assertArrayHasKey('name', $plugin);
        $this->assertStringContainsString(
            $pluginName,
            $plugin['name'],
            sprintf('Plugin %s should be in the list of plugins.', $pluginName)
        );
    }

    /**
     * Asserts no plugins.
     */
    protected function assertNoPlugins()
    {
        $pluginList = $this->terminusJsonResponse('self:plugin:list');
        $this->assertIsArray($pluginList);
        $this->assertEmpty($pluginList, 'Plugins list should be empty.');
    }

    /**
     * Asserts the command exists.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandExists(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringContainsString($commandName, $commandList);
    }

    /**
     * Asserts the command does not exist.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandNotExists(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringNotContainsString($commandName, $commandList);
    }
}
