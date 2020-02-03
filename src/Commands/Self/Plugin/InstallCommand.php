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
        'composer create-project --stability={stability} --prefer-source --keep-vcs -n -d {dir} {project}';
    const INVALID_PROJECT_MESSAGE = '{project} is not a valid Packagist project.';
    const USAGE_MESSAGE = 'terminus self:plugin:<install|add> <Packagist project 1> [Packagist project 2] ...';

    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @param array $projects A list of one or more plugin projects to install
     * @option string $stability Version stability such as stable, beta, alpha, etc.
     *
     * @usage <project 1> [project 2] ...
     */
    public function install(array $projects, $options = ['stability' => 'stable',])
    {
        foreach ($projects as $project_name) {
            if ($this->validateProject($project_name)) {
                $results = $this->doInstallation($project_name, $options['stability']);
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
     * @param string $stability stable, beta, alpha, etc
     * @return array Results from the install command
     */
    private function doInstallation($project_name, $stability)
    {
        $plugin_name = PluginInfo::getPluginNameFromProjectName($project_name);
        $config = $this->getConfig();
        $plugins_dir = $config->get('plugins_dir');
        $install_dir = $plugins_dir . DIRECTORY_SEPARATOR . $plugin_name;
        $this->ensureDirectoryExists($install_dir);

        $command = str_replace(
            ['{stability}', '{dir}', '{project}',],
            [$stability, $plugins_dir, $project_name,],
            self::INSTALL_COMMAND
        );
        $results = $this->runCommand($command);
        $this->log()->notice('Installed {project_name}.', compact('project_name'));
        return $results;
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
