<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Installs a Terminus plugin using Composer.
 *
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    const ALREADY_INSTALLED_MESSAGE = '{project} is already installed.';
    const INVALID_PROJECT_MESSAGE = '{project} is not a valid Packagist project.';
    const USAGE_MESSAGE = 'terminus self:plugin:<install|add> <Packagist project 1> [Packagist project 2] ...';

    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @param array $projects
     *   A list of one or more plugin projects to install. Projects may include version constraints.
     *
     * @usage <project 1> [project 2] ...
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function install(array $projects)
    {
        $projects = $this->convertPathProjects($projects);
        foreach ($projects as $projectName => $installationPath) {
            if ($this->validateProject($projectName, $installationPath)) {
                $results = $this->doInstallation($projectName, $installationPath);
                // TODO Improve messaging
                $this->log()->notice($results['output']);
            }
        }
    }

    /**
     * Check for minimum plugin command requirements.
     *
     * @hook validate self:plugin:install
     *
     * @param CommandData $commandData
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
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
     *
     * @param array $projects
     *
     * @return array
     *  - key is project name;
     *  - value is path toa local installation (if exists). Otherwise - NULL.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function convertPathProjects(array $projects): array
    {
        $convertedProjects = [];

        foreach ($projects as $projectNameOrPath) {
            if (!$this->hasProjectAtPath($projectNameOrPath)) {
                // No project was found, presume the parameter is a project, and it has no path.
                $convertedProjects[$this->getComposerProjectName($projectNameOrPath)] = null;
            } else {
                $projectName = $this->getProjectNameFromPath($projectNameOrPath);
                // A project name was found at the path, so record the name and its path.
                $convertedProjects[$this->getComposerProjectName($projectName)] = $projectNameOrPath;
            }
        }

        return $convertedProjects;
    }

    /**
     * Determines whether the given path contains a composer project.
     */
    protected function hasProjectAtPath($projectNameOrPath): bool
    {
        // If the specified path does not exist or does not have a composer.json file, presume it is a project.
        $composerJson = $projectNameOrPath . DIRECTORY_SEPARATOR . 'composer.json';

        return is_dir($projectNameOrPath) && file_exists($composerJson);
    }

    /**
     * Installs the plugin.
     *
     * @param string $projectName
     *   Name of project to be installed.
     * @param string|null $installationPath
     *   If not NULL, install as a path repository.
     *
     * @return array
     *   Results from the "install" command.
     */
    private function doInstallation(string $projectName, ?string $installationPath)
    {
        return $this->installProject($projectName, $installationPath);
    }

    /**
     * Validate given project is valid.
     *
     * @param string $projectName
     * @param string|null $installationPath
     *
     * @return bool
     */
    private function validateProject(string $projectName, ?string $installationPath): bool
    {
        if (null === $installationPath
            && !PluginInfo::checkWhetherPackagistProject($projectName, $this->getLocalMachine())
        ) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, ['project' => $projectName,]);
            return false;
        }

        if ($this->isInstalled($projectName)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, ['project' => $projectName,]);
            return false;
        }

        return true;
    }

    /**
     * Returns the project name.
     *
     * @param string $project
     *
     * @return string
     */
    private function getComposerProjectName(string $project)
    {
        $parts = explode('/', $project);
        if (1 === count($parts)) {
            return sprintf('pantheon-systems/%s', $project);
        }

        return $project;
    }
}
