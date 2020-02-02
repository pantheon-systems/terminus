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
    const COMPOSER_INSTALL_COMMAND =
        'composer create-project --stability=%s --prefer-source --keep-vcs -n -d %s %s:~%s';
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
        self::ensureDirectoryExists($install_dir);

        $command = sprintf(
            self::COMPOSER_INSTALL_COMMAND,
            $stability,
            $plugins_dir,
            $project_name,
            PluginInfo::getMajorVersionFromVersion($config->get('version'))
        );
        $results = $this->runCommand($command);
        return $results;
    }

    /**
     * @param string $project_name
     * @return bool
     */
    private function validateProject($project_name)
    {
        if (!self::isValidPackagistProject($project_name)) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        if ($this->isInstalled($project_name)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     * @param int $permissions
     */
    private static function ensureDirectoryExists($path, $permissions = 0755)
    {
        if (!is_dir($path)) {
            mkdir($path, $permissions, true);
        }
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project_name Name of plugin package to install
     * @return bool True if valid, false otherwise
     */
    private static function isValidPackagistProject($project_name)
    {
        // Search for the Packagist project.
        exec(sprintf(PluginInfo::VALIDATION_COMMAND, $project_name), $items);
        if (empty($items)) {
            return false;
        }
        $item = array_shift($items);
        return ($item === $project_name);
    }
}
