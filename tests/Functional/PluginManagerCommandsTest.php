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
    protected const TEST_PLUGIN_NAME = 'terminus-plugin-example';
    protected const TEST_PLUGIN_NAMES = [
        'terminus-plugin-example',
        'terminus-composer-plugin',
        'terminus-rsync-plugin',
    ];

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\InstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\ListCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\ReloadCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\SearchCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\UninstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\UpdateCommand
     *
     * @group plugins
     * @group long
     */
    public function testPluginsCommands()
    {
        $filesystem = new Filesystem();

        // Define test plugin packages.
        $testPluginPackage = 'pantheon-systems/' . self::TEST_PLUGIN_NAME;
        $testPluginPackages = array_map(
            fn($package) => 'pantheon-systems/' . $package,
            self::TEST_PLUGIN_NAMES
        );

        // Clean up.
        $filesystem->remove([
            $this->getPluginsDir(),
            $this->getPlugins2Dir(),
            $this->getDependenciesBaseDir(),
            $this->getBaseDir(),
        ]);

        // List commands to check that plugin commands are not available.
        $this->assertCommandDoesNotExist(self::HELLO_COMMAND);
        $this->assertNoPlugins();

        // Search plugin.
        $pluginList = $this->terminusJsonResponse(sprintf('self:plugin:search %s', self::TEST_PLUGIN_NAME));
        $this->assertIsArray($pluginList);
        $this->assertNotEmpty($pluginList);
        $plugin = reset($pluginList);
        $this->assertIsArray($plugin);
        $this->assertArrayHasKey('name', $plugin);
        $this->assertStringContainsString(
            $testPluginPackage,
            $plugin['name'],
            sprintf('Plugin search result should contain %s plugin.', $testPluginPackage)
        );

        // Install plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:install %s', $testPluginPackage));
        $this->assertStringContainsString(
            sprintf('Installed %s', $testPluginPackage),
            $pluginList,
            'Terminus plugin installation failed.'
        );
        $this->assertPluginExists(self::TEST_PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try updating plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:update %s', $testPluginPackage));
        $this->assertStringContainsString(
            'Nothing to install, update or remove',
            $pluginList,
            'Terminus plugin update failed.'
        );
        $this->assertPluginExists(self::TEST_PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try reloading plugins.
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:reload');
        $this->assertStringContainsString('Plugins reload done', $pluginList, 'Terminus plugin reload failed.');
        $this->assertPluginExists(self::TEST_PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Try uninstalling plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:uninstall %s', $testPluginPackage));
        $this->assertStringContainsString(
            sprintf('Uninstalled %s', $testPluginPackage),
            $pluginList,
            'Terminus plugin uninstall failed.'
        );
        $this->assertNoPlugins();
        $this->assertCommandDoesNotExist(self::HELLO_COMMAND);

        // Migrate Terminus 2 plugins.
        $this->assertCommandExists('self:plugin:migrate');
        $this->installTerminus2Plugins($testPluginPackages);
        $this->terminusWithStderrRedirected('self:plugin:migrate');
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:list');
        foreach (self::TEST_PLUGIN_NAMES as $plugin) {
            $this->assertStringContainsString(
                $plugin,
                $pluginList,
                'Terminus plugin installation failed.'
            );
        }

        // Create new plugin.
        $testPluginName = 'terminus-test/newplugin';
        $tempPluginFile = $filesystem->tempnam(sys_get_temp_dir(), 'terminustest');
        if ($filesystem->exists($tempPluginFile)) {
            $filesystem->remove($tempPluginFile);
        }
        $pluginList = $this->terminusWithStderrRedirected(
            sprintf('self:plugin:create %s --project-name=%s', $tempPluginFile, $testPluginName)
        );
        $this->assertStringContainsString(
            sprintf('Installed %s:@dev', $testPluginName),
            $pluginList,
            'Terminus plugin creation failed.'
        );
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Uninstall recently created plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:uninstall %s', $testPluginName));
        $this->assertStringContainsString(
            sprintf('Uninstalled %s', $testPluginName),
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
        $this->assertNotEmpty($pluginName);
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:list');
        $this->assertIsString($pluginList);
        $this->assertNotEmpty($pluginList);
        $this->assertStringContainsString(
            $pluginName,
            $pluginList,
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
