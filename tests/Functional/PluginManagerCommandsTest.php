<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class DomainCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PluginManagerCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Self\ListCommand
     * @covers \Pantheon\Terminus\Commands\Self\ReloadCommand
     * @covers \Pantheon\Terminus\Commands\Self\InstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\UpdateCommand
     * @covers \Pantheon\Terminus\Commands\Self\UninstallCommand
     * @covers \Pantheon\Terminus\Commands\Self\SearchCommand
     *
     * @group plugins
     * @group long
     */
    public function testPluginsOperations()
    {

        $filesystem = new Filesystem();
        $pluginsDir = $this->getPluginsDir();
        $dependenciesBaseDir = $this->getDependenciesBaseDir();
        $filesystem->remove([$pluginsDir, $dependenciesBaseDir]);

        // LIST COMMANDS TO CHECK THAT PLUGIN COMMANDS ARE NOT AVAILABLE
        $command = 'hello';
        $output = $this->terminus("list");
        $this->assertStringNotContainsString($command, $output);

        // LIST PLUGINS
        $results = $this->terminusWithStderrRedirected("self:plugin:list");
        $this->assertStringContainsString(
            "You have no plugins installed",
            $results,
            "Terminus plugins should be empty at this point."
        );

        // SEARCH PLUGIN
        $plugin = 'plugin-example';
        $results = $this->terminusJsonResponse("self:plugin:search $plugin");
        $this->assertIsArray($results, "Returned values from self:plugin:search should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of plugins should be greater than 0"
        );
        $this->assertStringContainsString(
            'pantheon-systems/terminus-plugin-example',
            $results[0]['name'],
            "Terminus plugin search didn't return the expected plugin."
        );

        // INSTALL PLUGIN
        $plugin = 'pantheon-systems/terminus-plugin-example';
        $results = $this->terminusWithStderrRedirected("self:plugin:install $plugin");
        $this->assertStringContainsString("Installed $plugin", $results, "Terminus plugin installation failed.");

        // LIST PLUGINS AGAIN
        $results = $this->terminusJsonResponse("self:plugin:list");
        $this->assertIsArray($results, "Returned values from self:plugin:list should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of plugins should be greater than 0"
        );
        $this->assertStringContainsString(
            'terminus-plugin-example',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );

        // LIST COMMANDS AGAIN
        $output = $this->terminus("list");
        $this->assertStringContainsString($command, $output);

        // TRY UPDATING PLUGIN
        $plugin = 'pantheon-systems/terminus-plugin-example';
        $results = $this->terminusWithStderrRedirected("self:plugin:update $plugin");
        $this->assertStringContainsString(
            "Nothing to install, update or remove",
            $results,
            "Terminus plugin update failed."
        );

        // LIST PLUGINS AGAIN
        $results = $this->terminusJsonResponse("self:plugin:list");
        $this->assertIsArray($results, "Returned values from self:plugin:list should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of plugins should be greater than 0"
        );
        $this->assertStringContainsString(
            'terminus-plugin-example',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );

        // LIST COMMANDS AGAIN
        $output = $this->terminus("list");
        $this->assertStringContainsString($command, $output);

        // TRY RELOADING PLUGINS
        $results = $this->terminusWithStderrRedirected("self:plugin:reload");
        $this->assertStringContainsString("Plugins reload done", $results, "Terminus plugin reload failed.");

        // LIST PLUGINS AGAIN
        $results = $this->terminusJsonResponse("self:plugin:list");
        $this->assertIsArray($results, "Returned values from self:plugin:list should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of plugins should be greater than 0"
        );
        $this->assertStringContainsString(
            'terminus-plugin-example',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );

        // LIST COMMANDS AGAIN
        $output = $this->terminus("list");
        $this->assertStringContainsString($command, $output);

        // TRY UNINSTALLING PLUGIN
        $plugin = 'pantheon-systems/terminus-plugin-example';
        $results = $this->terminusWithStderrRedirected("self:plugin:uninstall $plugin");
        $this->assertStringContainsString("Uninstalled $plugin", $results, "Terminus plugin uninstall failed.");

        // LIST PLUGINS
        $results = $this->terminusWithStderrRedirected("self:plugin:list");
        $this->assertStringContainsString(
            "You have no plugins installed",
            $results,
            "Terminus plugins should be empty at this point."
        );

        // LIST COMMANDS AGAIN TO CHECK THAT PLUGIN COMMANDS ARE NOT AVAILABLE
        $output = $this->terminus("list");
        $this->assertStringNotContainsString($command, $output);

        // CREATE NEW PLUGIN.
        $tempfile = $filesystem->tempnam(sys_get_temp_dir(), 'terminustest');
        if ($filesystem->exists($tempfile)) {
            $filesystem->remove($tempfile);
        }
        $results = $this->terminusWithStderrRedirected("self:plugin:create ${tempfile}");
        $this->assertStringContainsString(
            "Installed pantheon-systems/terminus-plugin-example:@dev",
            $results,
            "Terminus plugin creation failed."
        );

        // LIST COMMANDS AGAIN
        $output = $this->terminus("list");
        $this->assertStringContainsString($command, $output);

        // UNINSTALL RECENTLY CREATED PLUGIN
        $plugin = 'pantheon-systems/terminus-plugin-example';
        $results = $this->terminusWithStderrRedirected("self:plugin:uninstall $plugin");
        $this->assertStringContainsString("Uninstalled $plugin", $results, "Terminus plugin uninstall failed.");

        // CLEANUP FOLDER.
        $filesystem->remove($tempfile);
    }
}
