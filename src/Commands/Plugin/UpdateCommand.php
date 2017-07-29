<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Composer\Semver\Comparator;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Plugin
 */
class UpdateCommand extends PluginBaseCommand
{

    /**
     * Update one or more Terminus plugins.
     *
     *
     * @command plugin:update
     * @aliases plugin:upgrade plugin:up
     *
     * @option array $plugins A list of one or more installed plugins to update
     *
     * @usage <plugin-name-1|all> [plugin-name-2] ...
     */
    public function update(array $plugins)
    {
        // Check for minimum plugin command requirements.
        $this->checkRequirements();

        // @TODO: Add the ability to prompt for plugins to update.

        if (empty($plugins)) {
            $plugins = array('all');
        }

        if ($plugins[0] == 'all') {
            $plugins_dir = $this->getPluginDir();
            $plugins = $this->getPluginProjects($plugins_dir);
            if (empty($plugins[0])) {
                $message = "You have no plugins installed.";
                $this->log()->notice($message);
                return false;
            }
        }
        foreach ($plugins as $plugin) {
            $this->updatePlugin($plugin);
        }
    }

    /**
     * Update a specific plugin.
     *
     * @param string $plugin Plugin name
     */
    protected function updatePlugin($plugin)
    {
        $plugins_dir = $this->getPluginDir();
        $plugin_dir = $plugins_dir . $plugin;
        if (!is_dir("$plugin_dir")) {
            $message = "{$plugin} is not installed.";
            throw new TerminusNotFoundException($message);
        }
        $messages = array();
        $message = "Updating {$plugin}...";
        $this->log()->notice($message);
        $method = $this->getInstallMethod($plugin);
        switch ($method) {
            case 'git':
                // Compare installed version to the latest release.
                $installed_version = $this->getInstalledVersion($plugin_dir);
                $latest_version = $this->getLatestVersion($plugin_dir);
                if ($installed_version != 'unknown' && $latest_version != 'unknown') {
                    if (Comparator::greaterThan($latest_version, $installed_version)) {
                        exec("cd \"$plugin_dir\" && git checkout {$latest_version}", $messages);
                    } else {
                        $messages[] = "Already up-to-date.";
                    }
                } else {
                    $messages[] = "Unable to update.  Semver compliance issue with tagged release.";
                }
                break;

            case 'composer':
                // Determine the project name.
                $composer_info = $this->getComposerInfo($plugin);
                $project = $composer_info['name'];
                $packagist_url = "https://packagist.org/packages/{$project}";
                if ($this->isValidUrl($packagist_url)) {
                    // Get the Terminus major version.
                    $terminus_major_version = $this->getTerminusMajorVersion();
                    // Backup the plugin directory, just in case.
                    $datetime = date('YmdHi', time());
                    $backup_directory = $plugins_dir . '..' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . $datetime;
                    exec("mkdir -p {$backup_directory} && tar czvf {$backup_directory}" . DIRECTORY_SEPARATOR . "backup.tar.gz \"{$plugin_dir}\"", $backup_messages);
                    // Create a new project via Composer.
                    $composer_command = "composer create-project --prefer-source --keep-vcs -n -d {$plugins_dir} {$project}:~{$terminus_major_version}";
                    exec("rm -rf \"{$plugin_dir}\" && {$composer_command}", $install_messages);
                    $messages = array_merge($backup_messages, $install_messages);
                    $messages[] = "Backed up the project to {$backup_directory}" . DIRECTORY_SEPARATOR . "backup.tar.gz.";
                } else {
                    $messages[] = "Unable to update.  {$packagist_url} is not a valid Packagist project.";
                }
                break;

            default:
                $messages[] = "Unable to update.  Plugin is not a valid Packagist project.";
        }
        foreach ($messages as $message) {
            $this->log()->notice($message);
        }
    }
}
