<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusException;

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

    /**
     * Update one or more Terminus plugins.
     *
     * @command self:plugin:update
     * @aliases self:plugin:upgrade plugin:up plugin:update plugin:upgrade
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
                function ($project) use ($logger) {
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
        $config = $this->getConfig();
        $plugin_info = $plugin->getInfo();
        $project = $plugin_info['name'];
        $plugin_dir = $plugin->getPath();
        $original_plugins_dir = $config->get('plugins_dir');
        $original_dependencies_dir = $config->get('terminus_dependencies_dir');
        $folders = $this->updateTerminusDependencies($original_plugins_dir, $original_dependencies_dir);
        $plugins_dir = $folders['plugins_dir'];
        $dependencies_dir = $folders['dependencies_dir'];
        $messages = [];
        $this->log()->notice(self::UPDATING_MESSAGE, $plugin_info);
        if ($plugin->isValidPackagistProject()) {
            try {
                $results = $this->runComposerUpdate($dependencies_dir, $project);
                if ($results['output']) {
                    $messages[] = $results['output'];
                }
                if ($results['stderr']) {
                    $messages[] = $results['stderr'];
                }
                if ($results['exit_code'] !== 0) {
                    throw new TerminusException('Error updating packages in terminus-depedencies.');
                }

                $this->replaceFolder($plugins_dir, $original_plugins_dir);
                $this->replaceFolder($dependencies_dir, $original_dependencies_dir);
            } catch (TerminusException $e) {
                $this->log()->error($e->getMessage());
            }
        } else {
            $messages[] = str_replace(['{project}'], [$project], self::INVALID_PROJECT_MESSAGE);
        }
        foreach ($messages as $message) {
            $this->log()->notice($message, $plugin_info);
        }
    }
}
