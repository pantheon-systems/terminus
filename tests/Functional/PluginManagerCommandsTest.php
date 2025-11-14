<?php

namespace Pantheon\Terminus\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Class PluginManagerCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PluginManagerCommandsTest extends TerminusTestBase
{
    protected const HELLO_COMMAND = 'hello';
    protected const TEST_PLUGIN_NAME = 'terminus-plugin-example';
    protected const TEST_PLUGIN_GIT_URL = 'https://github.com/pantheon-systems/terminus-plugin-example.git';
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
            fn ($package) => 'pantheon-systems/' . $package,
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

        // Install plugin.
        $pluginList = $this->terminusWithStderrRedirected(sprintf('self:plugin:install %s', self::TEST_PLUGIN_GIT_URL));
        $this->assertStringContainsString(
            sprintf('Installed %s', $testPluginPackage),
            $pluginList,
            'Terminus plugin installation failed.'
        );
        $this->assertPluginExists(self::TEST_PLUGIN_NAME);
        $this->assertCommandExists(self::HELLO_COMMAND);

        // Migrate Terminus 2 plugins.
        $this->assertCommandExists('self:plugin:migrate');
        $this->installTerminus2Plugins($testPluginPackages);
        $this->terminusWithStderrRedirected('self:plugin:migrate');
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:list --format=json');
        foreach (self::TEST_PLUGIN_NAMES as $plugin) {
            $this->assertStringContainsString(
                $plugin,
                $pluginList,
                'Terminus plugin migrate failed.'
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
     * @test
     * @covers \Pantheon\Terminus\Commands\Self\Plugin\InstallCommand
     *
     * Regression test to verify that COMPOSER_AUDIT_BLOCK_INSECURE environment variable
     * is properly forwarded to Composer subprocess during plugin installation.
     *
     * This test verifies the fix for the bug where COMPOSER_AUDIT_BLOCK_INSECURE=0
     * was not being forwarded to Composer, causing plugin installations to fail
     * with security advisory errors even when the user explicitly opted out.
     *
     * Uses terminus-build-tools-plugin which has known security advisories in its
     * dependencies (e.g., symfony/process v5.4.40, twig/twig v3.11.1) to verify
     * that the env var forwarding actually works end-to-end.
     *
     * @group plugins
     * @group long
     */
    public function testPluginInstallRespectsComposerAuditBlockInsecureEnv()
    {
        $filesystem = new Filesystem();
        // Use a plugin with known security advisories to test the env forwarding
        $testPluginPackage = 'pantheon-systems/terminus-build-tools-plugin';

        // Clean up.
        $filesystem->remove([
            $this->getPluginsDir(),
            $this->getPlugins2Dir(),
            $this->getDependenciesBaseDir(),
            $this->getBaseDir(),
        ]);

        // Uninstall plugin if it exists
        $this->terminus('self:plugin:uninstall ' . $testPluginPackage, [], false);

        // Test that plugin installation works with COMPOSER_AUDIT_BLOCK_INSECURE=0 set.
        // This verifies that the environment variable is properly forwarded to Composer.
        // Without this env var, Composer 2.9.1+ would block the installation due to
        // security advisories in the plugin's dependencies.
        $env = array_merge($this->env, ['COMPOSER_AUDIT_BLOCK_INSECURE' => '0']);
        [$output, $exitCode, $error] = static::callTerminus(
            sprintf('self:plugin:install %s', $testPluginPackage),
            null,
            $env
        );

        // The installation should succeed (exit code 0) when COMPOSER_AUDIT_BLOCK_INSECURE=0 is set.
        // If the env var is not forwarded, Composer would fail with security advisory errors.
        $this->assertEquals(
            0,
            $exitCode,
            sprintf(
                'Plugin installation should succeed with COMPOSER_AUDIT_BLOCK_INSECURE=0. ' .
                'If this fails, the env var may not be forwarded to Composer. ' .
                'Output: %s, Error: %s',
                $output,
                $error
            )
        );

        // Verify the plugin was actually installed
        $this->assertStringContainsString(
            sprintf('Installed %s', $testPluginPackage),
            $output . $error,
            'Plugin installation output should indicate success.'
        );

        // Verify no security advisory blocking errors in the output
        $combinedOutput = $output . $error;
        $this->assertStringNotContainsString(
            'are affected by security advisories',
            $combinedOutput,
            'Plugin installation should not show security advisory blocking errors '
            . 'when COMPOSER_AUDIT_BLOCK_INSECURE=0 is set.'
        );

        // Cleanup
        $this->terminus('self:plugin:uninstall ' . $testPluginPackage, [], false);
    }

    /**
     * Install Terminus 2 plugins.
     *
     * @param array $plugins
     */
    protected function installTerminus2Plugins(array $plugins): void
    {
        $filesystem = new Filesystem();
        $plugins2_dir = $this->getPlugins2Dir();
        if (is_dir($plugins2_dir)) {
            $filesystem->remove($plugins2_dir);
        }
        $filesystem->mkdir($plugins2_dir);
        foreach ($plugins as $plugin) {
            exec(sprintf('composer create-project --no-dev -d %s %s', $plugins2_dir, $plugin), $output, $exitCode);
            $this->assertEquals(0, $exitCode, implode("\n", $output));
        }
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
        $pluginList = $this->terminusWithStderrRedirected('self:plugin:list --format=json');
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
        $pluginList = $this->terminusJsonResponse('self:plugin:list --format=json');
        $this->assertIsArray($pluginList);
        $this->assertEmpty($pluginList, 'Plugins list should be empty.');
    }
}
