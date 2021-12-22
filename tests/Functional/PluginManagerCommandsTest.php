<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\TerminusUtilsTrait;
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
    use TerminusUtilsTrait;

    protected const HELLO_COMMAND = 'hello';
    protected const PLUGIN_NAME = 'terminus-plugin-example';
    protected const PLUGIN_PACKAGE = 'pantheon-systems/terminus-plugin-example';

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
        $this->assertCommandDoesNotExist(self::HELLO_COMMAND);
        $this->assertNoPlugins();

        // Search plugin.
        $pluginList = $this->terminusJsonResponse(sprintf('self:plugin:search %s', self::PLUGIN_NAME));
        $this->assertIsArray($pluginList);
        $this->assertNotEmpty($pluginList);
        $plugin = reset($pluginList);
        $this->assertIsArray($plugin);
        $this->assertArrayHasKey('name', $plugin);
        $this->assertStringContainsString(
            self::PLUGIN_PACKAGE,
            $plugin['name'],
            sprintf('Plugin search result should contain %s plugin.', self::PLUGIN_PACKAGE)
        );

        // Install plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:install %s', self::PLUGIN_PACKAGE));
        $this->assertStringContainsString(
            sprintf('Installed %s', self::PLUGIN_PACKAGE),
            $pluginList,
            'Terminus plugin installation failed.'
        );
        $this->assertPluginExists(self::PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try updating plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:update %s', self::PLUGIN_PACKAGE));
        $this->assertStringContainsString(
            'Nothing to install, update or remove',
            $pluginList,
            'Terminus plugin update failed.'
        );
        $this->assertPluginExists(self::PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try reloading plugins.
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:reload');
        $this->assertStringContainsString('Plugins reload done', $pluginList, 'Terminus plugin reload failed.');
        $this->assertPluginExists(self::PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try uninstalling plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:uninstall %s', self::PLUGIN_PACKAGE));
        $this->assertStringContainsString(
            sprintf('Uninstalled %s', self::PLUGIN_PACKAGE),
            $pluginList,
            'Terminus plugin uninstall failed.'
        );
        $this->assertNoPlugins();
        $this->assertCommandDoesNotExist(self::HELLO_COMMAND);

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
        $this->assertCommandExists(self::HELLO_COMMAND);

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

}
