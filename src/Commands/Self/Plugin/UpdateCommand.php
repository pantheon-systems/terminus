<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Composer\Semver\Comparator;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Updates installed Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Add the ability to prompt for plugins to update.
 */
class UpdateCommand extends PluginBaseCommand
{
    const NO_PLUGINS_MESSAGE = 'You have no plugins installed.';

    /**
     * Update one or more Terminus plugins.
     *
     * @command self:plugin:update
     * @aliases self:plugin:upgrade self:plugin:up
     *
     * @param array $plugins A list of one or more installed plugins to update
     *
     * @usage <project|all> [project] ...
     */
    public function update(array $projects = ['all',])
    {
        $plugins = $this->getPluginProjects();
        $logger = $this->log();

        if (empty($plugins)) {
            $logger->warning(self::NO_PLUGINS_MESSAGE);
            return;
        }

        if ($projects[0] !== 'all') {
            $plugins = array_map(
                function($project) use ($logger) {
                    try {
                        return $this->getPluginProject($project);
                    } catch (TerminusNotFoundException $e) {
                        $logger->error($e->getMessage());
                    }
                },
                $projects
            );
        }

        foreach ($plugins as $plugin_info) {
            $this->doUpdate($plugin_info);
        }
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:install
     * @param CommandData $commandData
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();
    }

    /**
     * Update a specific plugin.
     *
     * @param array $plugin_info Information about the installed plugin
     */
    protected function doUpdate($plugin_info)
    {
        $messages = [];
        $message = "Updating {$plugin_info['project']}...";
        $this->log()->notice($message);
        switch ($plugin_info['method']) {
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
                    $backup_directory = $plugins_dir . '..' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR
                        . 'plugins' . DIRECTORY_SEPARATOR . $project . DIRECTORY_SEPARATOR . $datetime;
                    exec(
                        "mkdir -p {$backup_directory} && tar czvf {$backup_directory}"
                        . DIRECTORY_SEPARATOR . "backup.tar.gz \"{$plugin_dir}\"",
                        $backup_messages
                    );
                    // Create a new project via Composer.
                    $composer_command = "composer create-project --prefer-source --keep-vcs -n -d "
                        . "{$plugins_dir} {$project}:~{$terminus_major_version}";
                    exec("rm -rf \"{$plugin_dir}\" && {$composer_command}", $install_messages);
                    $messages = array_merge($backup_messages, $install_messages);
                    $messages[] =
                        "Backed up the project to {$backup_directory}" . DIRECTORY_SEPARATOR . "backup.tar.gz.";
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
