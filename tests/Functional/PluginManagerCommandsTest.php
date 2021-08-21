<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

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
        $pluginsDir = $this->getPluginsDir();
        $dependenciesBaseDir = $this->getDependenciesBaseDir();
        $this->removeDir($pluginsDir);
        $this->removeDir($dependenciesBaseDir);

        // LIST COMMANDS TO CHECK THAT PLUGIN COMMANDS ARE NOT AVAILABLE
        $command = 'build:project:create';
        $this->terminus("list | grep $command", 1);

        // LIST PLUGINS
        $results = $this->terminus("self:plugin:list 2>&1");
        $this->assertStringContainsString(
            "You have no plugins installed",
            $results,
            "Terminus plugins should be empty at this point."
        );

        // SEARCH PLUGIN
        $plugin = 'build-tools';
        $results = $this->terminusJsonResponse("self:plugin:search $plugin");
        $this->assertIsArray($results, "Returned values from self:plugin:search should be array");
        $this->assertGreaterThan(
            0,
            count($results),
            "Count of plugins should be greater than 0"
        );
        $this->assertStringContainsString(
            'pantheon-systems/terminus-build-tools-plugin',
            $results[0]['name'],
            "Terminus plugin search didn't return the expected plugin."
        );

        // INSTALL PLUGIN
        $plugin = 'pantheon-systems/terminus-build-tools-plugin:dev-add-github-actions-sodium-compat';
        $results = $this->terminus("self:plugin:install $plugin 2>&1");
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
            'terminus-build-tools-plugin',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );
        
        // LIST COMMANDS AGAIN
        $this->terminus("list | grep $command");

        // TRY UPDATING PLUGIN
        $plugin = 'pantheon-systems/terminus-build-tools-plugin';
        $results = $this->terminus("self:plugin:update $plugin 2>&1");
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
            'terminus-build-tools-plugin',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );

        // LIST COMMANDS AGAIN
        $this->terminus("list | grep $command");

        // TRY RELOADING PLUGINS
        $results = $this->terminus("self:plugin:reload 2>&1");
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
            'terminus-build-tools-plugin',
            $results[0]['name'],
            "Terminus plugin recently installed is not listed."
        );

        // LIST COMMANDS AGAIN
        $this->terminus("list | grep $command");

        // TRY UNINSTALLING PLUGIN
        $plugin = 'pantheon-systems/terminus-build-tools-plugin';
        $results = $this->terminus("self:plugin:uninstall $plugin 2>&1");
        $this->assertStringContainsString("Uninstalled $plugin", $results, "Terminus plugin uninstall failed.");

        // LIST PLUGINS
        $results = $this->terminus("self:plugin:list 2>&1");
        $this->assertStringContainsString(
            "You have no plugins installed",
            $results,
            "Terminus plugins should be empty at this point."
        );

        // LIST COMMANDS AGAIN TO CHECK THAT PLUGIN COMMANDS ARE NOT AVAILABLE
        $this->terminus("list | grep $command", 1);
    }

}
