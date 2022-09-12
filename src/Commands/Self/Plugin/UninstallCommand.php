<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;
use Symfony\Component\Process\Exception\RuntimeException;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * UninstallCommand class.
 *
 * Removes Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class UninstallCommand extends PluginBaseCommand
{
    const NOT_INSTALLED_MESSAGE = '{project} is not installed.';
    const SUCCESS_MESSAGE = '{project} was removed successfully.';
    const USAGE_MESSAGE = 'terminus self:plugin:<uninstall|remove> <project> [project 2] ...';
    const UNINSTALL_COMMAND =
    'composer remove -d {dir} {project} --no-update';
    const REMOVE_PATH_REPO_COMMAND =
        'composer config -d {dir} --unset repositories.{name}';

    /**
     * Remove one or more Terminus plugins.
     *
     * @command self:plugin:uninstall
     * @aliases self:plugin:remove self:plugin:rm self:plugin:delete plugin:uninstall plugin:remove plugin:rm plugin:delete
     *
     * @usage <project> [project] ... Uninstalls the indicated plugins.
     *
     * @param array $projects A list of one or more installed projects or plugins to remove
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function uninstall(array $projects)
    {
        foreach ($projects as $project) {
            try {
                $this->doUninstallation($this->getPlugin($project));
                $this->log()->notice(self::SUCCESS_MESSAGE, compact('project'));
            } catch (RuntimeException $e) {
                $this->log()->error(self::NOT_INSTALLED_MESSAGE, compact('project'));
            }
        }
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:uninstall
     *
     * @param CommandData $commandData
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();

        if (empty($commandData->input()->getArgument('projects'))) {
            throw new TerminusNotFoundException(self::USAGE_MESSAGE);
        }
    }

    /**
     * @param PluginInfo $project
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function doUninstallation(PluginInfo $project)
    {
        $config = $this->getConfig();
        $original_plugins_dir = $config->get('plugins_dir');
        $original_dependencies_dir = $this->getTerminusDependenciesDir();
        $folders = $this->updateTerminusDependencies($original_plugins_dir, $original_dependencies_dir);
        $plugins_dir = $folders['plugins_dir'];
        $dependencies_dir = $folders['dependencies_dir'];
        try {
            $project_name = $project->getName();

            // First remove from terminus-plugins.
            $command = str_replace(
                ['{project}'],
                [$project_name],
                self::UNINSTALL_COMMAND
            );
            $command = self::populateComposerWorkingDir($command, $plugins_dir);
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException('Error removing package in terminus-dependencies.');
            }

            // Then, Update terminus-dependencies composer.
            $results = $this->runComposerUpdate($dependencies_dir);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException('Error running composer update in terminus-dependencies.');
            }

            // Cleanup path repositories if they exist.
            foreach ([$plugins_dir, $dependencies_dir] as $dir) {
                $command = str_replace(
                    ['{name}'],
                    [$project_name],
                    self::REMOVE_PATH_REPO_COMMAND
                );
                $command = self::populateComposerWorkingDir($command, $dir);
                $results = $this->runCommand($command);
                if ($results['exit_code'] !== 0) {
                    throw new TerminusException('Error removing path repository in ' . basename($dir));
                }
            }

            $this->replaceFolder($plugins_dir, $original_plugins_dir);
            $this->replaceFolder($dependencies_dir, $original_dependencies_dir);

            $this->log()->notice('Uninstalled {project_name}.', compact('project_name'));
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }
    }
}
