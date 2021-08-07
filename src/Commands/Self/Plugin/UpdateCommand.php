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
    const INVALID_PROJECT_MESSAGE = 'Unable to update: {project} is not a valid Packagist project.';
    const NO_PLUGINS_MESSAGE = 'You have no plugins installed.';
    const SEMVER_CANNOT_UPDATE_MESSAGE = 'Unable to update. Semver compliance issue with tagged release.';
    const UPDATING_MESSAGE = 'Updating {name}...';
    const UPDATE_COMMAND =
        'composer update -d {dir} {project} --with-all-dependencies';
    const BACKUP_COMMAND =
        "mkdir -p {backup_dir} && tar czvf {backup_dir}"
        . DIRECTORY_SEPARATOR . "backup.tar.gz \"{plugins_dir}\"";

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
        $project = $plugin_info['name'];
        $plugin_dir = $plugin->getPath();
        $messages = [];
        $this->log()->notice(self::UPDATING_MESSAGE, $plugin_info);
        if ($plugin->getInstallationMethod() === 'composer') {
            // Determine the project name.
            if ($plugin->isValidPackagistProject()) {
                // Get the Terminus major version.
                $terminus_major_version = $this->getTerminusMajorVersion();
                // Backup the plugins directory, just in case.
                $datetime = date('YmdHi', time());
                $backup_directory = str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    "$plugins_dir/../backup/plugins/$datetime"
                );
                $command = str_replace(
                    ['{backup_dir}', '{plugins_dir}',],
                    [$backup_directory, $plugins_dir,],
                    self::BACKUP_COMMAND
                );
                $backup_messages = $this->runCommand($command);
                if ($backup_messages['output']) {
                    $messages[] = $backup_messages['output'];
                }
                if ($backup_messages['stderr']) {
                    $messages[] = $backup_messages['stderr'];
                }

                $command = str_replace(
                    ['{dir}', '{project}',],
                    [$plugins_dir, $project,],
                    self::UPDATE_COMMAND
                );
                $results = $this->runCommand($command);
                if ($results['output']) {
                    $messages[] = $results['output'];
                }
                if ($results['stderr']) {
                    $messages[] = $results['stderr'];
                }
                $messages[] =
                    "Backed up the project to {$backup_directory}" . DIRECTORY_SEPARATOR . "backup.tar.gz";
            } else {
                $messages[] = str_replace(['{project}'], [$project], self::INVALID_PROJECT_MESSAGE);
            }
        }
        else {
            $messages[] = str_replace(['{project}'], [$project], self::INVALID_PROJECT_MESSAGE);
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
