<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Installs a Terminus plugin using Composer.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    const ALREADY_INSTALLED_MESSAGE = '{project} is already installed.';
    const INSTALL_COMMAND =
        'composer require -d {dir} {project} --prefer-source';
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
        foreach ($projects as $project_name) {
            if ($this->validateProject($project_name)) {
                $results = $this->doInstallation($project_name);
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
     * @param string $project_name Name of project to be installed
     * @return array Results from the install command
     */
    private function doInstallation($project_name)
    {
        $plugin_name = PluginInfo::getPluginNameFromProjectName($project_name);
        $config = $this->getConfig();
        $plugins_dir = $config->get('plugins_dir');
        $dependencies_dir = $config->get('dependencies_dir');
        $this->ensureComposerJsonExists($plugins_dir, 'pantheon-systems/terminus-plugins');
        $this->ensureComposerJsonExists($dependencies_dir, 'pantheon-systems/terminus-dependencies');
        // @todo Kevin: Add path repo to terminus-dependencies dir, require plugin with *. Do I need this?


        $command = str_replace(
            ['{dir}', '{project}',],
            [$plugins_dir, $project_name,],
            self::INSTALL_COMMAND
        );
        $results = $this->runCommand($command);
        $this->log()->notice('Installed {project_name}.', compact('project_name'));
        return $results;
    }

    /**
     * @param string $path
     */
    private function ensureComposerJsonExists($path, $package_name)
    {
        $this->ensureDirectoryExists($path);
        if (!$this->getLocalMachine()->getFileSystem()->exists($path . '/composer.json')) {
            $this->runCommand("composer --working-dir=${path} init --name=${package_name} -n");
        }
    }

    /**
     * @param string $path
     * @param int $permissions
     */
    private function ensureDirectoryExists($path, $permissions = 0755)
    {
        $this->getLocalMachine()->getFileSystem()->mkdir($path, $permissions);
    }

    /**
     * @param string $project_name
     * @return bool
     */
    private function validateProject($project_name)
    {
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
