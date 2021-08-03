<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Composer\Semver\Comparator;
use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Updates installed Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Add the ability to prompt for plugins to update.
 */
class UpdateCommand extends PluginBaseCommand
{
    const ALREADY_UP_TO_DATE_MESSAGE = 'Already up-to-date.';
    const GIT_UPDATE_COMMAND = 'cd %s && git checkout %s';
    const INVALID_PROJECT_MESSAGE = 'Unable to update. {project} is not a valid Packagist project.';
    const NO_PLUGINS_MESSAGE = 'You have no plugins installed.';
    const SEMVER_CANNOT_UPDATE_MESSAGE = 'Unable to update. Semver compliance issue with tagged release.';
    const UPDATING_MESSAGE = 'Updating {name}...';

    /**
     * Update one or more Terminus plugins.
     *
     * @command self:plugin:update
     * @aliases self:plugin:upgrade self:plugin:up
     *
     * @param array $projects A list of one or more installed plugins to update
     *
     * @usage <project|all> [project] ...
     */
    public function update(array $projects)
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
                        return $this->getPlugin($project);
                    } catch (TerminusNotFoundException $e) {
                        $logger->error($e->getMessage());
                    }
                },
                $projects
            );
        }

        foreach ($plugins as $plugin) {
            if ($plugin) {
                $this->doUpdate($plugin);
            }
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
    protected function doUpdate($plugin)
    {
        $plugins_dir = $this->getPluginsDir();
        $plugin_info = $plugin->getInfo();
        $plugin_dir = $plugin->getPath();
        $messages = [];
        $this->log()->notice(self::UPDATING_MESSAGE, $plugin_info);
        switch ($plugin_info['method']) {
            case 'git':
                // Compare installed version to the latest release.
                $installed_version = $plugin_info['version'];
                $latest_version = $plugin_info['latest_version'];
                if (($installed_version !== PluginInfo::UNKNOWN_VERSION) && ($latest_version !== PluginInfo::UNKNOWN_VERSION)) {
                    if (Comparator::greaterThan($latest_version, $installed_version)) {
                        exec(
                            sprintf(self::GIT_UPDATE_COMMAND, $plugin_dir, $latest_version),
                            $messages
                        );
                    } else {
                        $messages[] = self::ALREADY_UP_TO_DATE_MESSAGE;
                    }
                } else {
                    $messages[] = self::SEMVER_CANNOT_UPDATE_MESSAGE;
                }
                break;

            case 'composer':
                // Determine the project name.
                $project = $plugin_info['name'];
                if ($plugin->isValidPackagistProject()) {
                    // Get the Terminus major version.
                    $terminus_major_version = $this->getTerminusMajorVersion();
                    // Backup the plugin directory, just in case.
                    $datetime = date('YmdHi', time());
                    $backup_directory = str_replace(
                        '/',
                        DIRECTORY_SEPARATOR,
                        "$plugins_dir/../backup/plugins/$project/$datetime"
                    );
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
                    $messages[] = self::INVALID_PROJECT_MESSAGE;
                }
                break;

            default:
                $messages[] = self::INVALID_PROJECT_MESSAGE;
        }
        foreach ($messages as $message) {
            $this->log()->notice($message, $plugin_info);
        }
    }

    /**
     * Check whether a URL is valid.
     *
     * @param string $url The URL to check
     * @return bool True if the URL returns a 200 status, false otherwise
     */
    protected function isValidPackagi($url = '')
    {
        if (!$url) {
            return false;
        }
        $headers = @get_headers($url);
        if (!isset($headers[0])) {
            return false;
        }
        return (strpos($headers[0], '200') !== false);
    }
}
