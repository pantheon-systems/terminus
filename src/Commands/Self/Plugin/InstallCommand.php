<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

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
     * @usage <Packagist project 1> [Packagist project 2] ...
     */
    public function install(array $projects, $options = ['stability' => 'stable',])
    {
        foreach ($projects as $project) {
            if ($this->validateProject($project)) {
                $messages = $this->doInstallation($project, $options['stability']);
                foreach ($messages as $message) {
                    $this->log()->notice($message);
                }
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
     * @param string $project Name of project to be installed
     * @param string $stability stable, beta, alpha, etc
     * @return array $messages
     */
    private function doInstallation($project, $stability)
    {
        exec(
            sprintf(
                self::COMPOSER_INSTALL_COMMAND,
                $stability,
                $this->getPluginDir(),
                $project,
                $this->getTerminusMajorVersion()
            ),
            $messages
        );
        return $messages;
    }

    /**
     * @param string $project
     * @return bool
     * @throws TerminusException If the plugin is already installed
     * @throws TerminusNotFoundException If the package is not valid
     */
    private function validateProject($project)
    {
        if (!$this->isValidPackagistProject($project)) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, compact('project'));
            return false;
        }

        if ($this->isInstalled($project)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, compact('project'));
            return false;
        }

        return true;
    }
}
