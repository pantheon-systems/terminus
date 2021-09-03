<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Installs a Terminus plugin using Composer.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    const ALREADY_INSTALLED_MESSAGE = '{project} is already installed.';
    const CONFIGURE_PATH_REPO_COMMAND =
        'composer config -d {dir} repositories.{name} path {path}';
    const INSTALL_COMMAND =
        'composer require -d {dir} {project} --no-update';
    const INVALID_PROJECT_MESSAGE = '{project} is not a valid Packagist project.';
    const USAGE_MESSAGE = 'terminus self:plugin:<install|add> <Packagist project 1> [Packagist project 2] ...';

    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @param array $projects A list of one or more plugin projects to install. Projects may include version constraints.
     *
     * @usage <project 1> [project 2] ...
     */
    public function install(array $projects)
    {
        $projects = $this->convertPathProjects($projects);
        foreach ($projects as $project_name => $instalation_path) {
            if ($this->validateProject($project_name)) {
                $results = $this->doInstallation($project_name, $instalation_path);
                // TODO Improve messaging
                $this->log()->notice($results['output']);
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

        if (empty($commandData->input()->getArgument('projects'))) {
            throw new TerminusNotFoundException(self::USAGE_MESSAGE);
        }
    }

    /**
     * Convert given projects into an array indexed by project name and path (if exists) as value.
     */
    protected function convertPathProjects($projects)
    {
        $resultList = [];

        foreach ($projects as $project_or_path) {
            if (!$this->hasProjectAtPath($project_or_path)) {
                // No project was found, presume the parameter is a project and it has no path
                $resultList[$project_or_path] = '';
            } else {
                $project_name = $this->getProjectNameFromPath($project_or_path);
                // A project name was found at the path, so record the name and its path
                $resultList[$project_name] = $project_or_path;
            }
        }

        return $resultList;
    }

    /**
     * Determines whether the given path contains a composer project.
     */
    protected function hasProjectAtPath($project_or_path)
    {
        // If the specified path does not exist or does not have a composer.json file, presume it is a project.
        $composerJson = $project_or_path . '/composer.json';
        return is_dir($project_or_path) && file_exists($composerJson);
    }

    /**
     * Gets project name from given path.
     */
    protected function getProjectNameFromPath($project_or_path)
    {
        $composerJson = $project_or_path . '/composer.json';
        $composerContents = file_get_contents($composerJson);
        // If the specified dir does not contain a terminus plugin, throw an error
        $composerData = json_decode($composerContents, true);
        if (!isset($composerData['type']) || ($composerData['type'] !== 'terminus-plugin')) {
            throw new TerminusException(
                'Cannot install from path {path} because the project there is not of type "terminus-plugin"',
                ['path' => $project_or_path]
            );
        }

        // If the specified dir does not have a name in the composer.json, throw an error
        if (empty($composerData['name'])) {
            throw new TerminusException(
                'Cannot install from path {path} because the project there does not have a name',
                ['path' => $project_or_path]
            );
        }

        // Finally, return the project name and let install command install it as normal.
        return $composerData['name'];
    }

    /**
     * @param string $project_name Name of project to be installed
     * @param string $instalation_path If not empty, install as a path repository
     * @return array Results from the install command
     */
    private function doInstallation($project_name, $instalation_path = '')
    {
        $plugin_name = PluginInfo::getPluginNameFromProjectName($project_name);
        $project_name_parts = explode(':', $project_name);
        $project_name_without_version = reset($project_name_parts);
        $config = $this->getConfig();
        $original_plugins_dir = $config->get('plugins_dir');
        $original_dependencies_dir = $config->get('terminus_dependencies_dir');
        $folders = $this->updateTerminusDependencies($original_plugins_dir, $original_dependencies_dir);
        $plugins_dir = $folders['plugins_dir'];
        $dependencies_dir = $folders['dependencies_dir'];
        try {
            if (!empty($instalation_path)) {
                // Update path repository in plugins dir and dependencies dir.
                foreach ([$plugins_dir, $dependencies_dir] as $dir) {
                    $command = str_replace(
                        ['{dir}', '{name}', '{path}',],
                        [$dir, $project_name_without_version, realpath($instalation_path),],
                        self::CONFIGURE_PATH_REPO_COMMAND
                    );
                    $results = $this->runCommand($command);
                    if ($results['exit_code'] !== 0) {
                        throw new TerminusException(
                            'Error configuring path repository in ' . basename($dir),
                            []
                        );
                    }
                }
            }

            $command = str_replace(
                ['{dir}', '{project}',],
                [$plugins_dir, $project_name,],
                self::INSTALL_COMMAND
            );
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error requiring package in terminus-plugins.',
                    []
                );
            }
            $results = $this->runComposerUpdate($dependencies_dir, $project_name_without_version);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error running composer update in terminus-dependencies.',
                    []
                );
            }
            $this->replaceFolder($plugins_dir, $original_plugins_dir);
            $this->replaceFolder($dependencies_dir, $original_dependencies_dir);
            $this->log()->notice('Installed {project_name}.', compact('project_name'));
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }

        return $results;
    }

    /**
     * Validate given project is valid. If project name does not include vendor, prefix it with pantheon-systems.
     *
     * @param string $project_name
     * @return bool
     */
    private function validateProject(&$project_name)
    {
        $parts = explode('/', $project_name);
        if (count($parts) === 1) {
            // No vendor name, add pantheon-systems as default.
            $project_name = "pantheon-systems/$project_name";
        }
        if (!PluginInfo::checkWhetherPackagistProject($project_name, $this->getLocalMachine())) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        if ($this->isInstalled($project_name)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        return true;
    }
}
