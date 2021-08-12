<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;
use Symfony\Component\Process\Exception\RuntimeException;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Removes Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Add the ability to prompt for plugins to remove.
 */
class UninstallCommand extends PluginBaseCommand
{
    const NOT_INSTALLED_MESSAGE = '{project} is not installed.';
    const SUCCESS_MESSAGE = '{project} was removed successfully.';
    const USAGE_MESSAGE = 'terminus self:plugin:<uninstall|remove> <project> [project 2] ...';
    const UNINSTALL_COMMAND =
    'composer remove -d {dir} {project} --no-update';

    /**
     * Remove one or more Terminus plugins.
     *
     * @command self:plugin:uninstall
     * @aliases self:plugin:remove self:plugin:rm self:plugin:delete
     *
     * @param array $projects A list of one or more installed projects or plugins to remove
     *
     * @usage <project> [project] ... Uninstalls the indicated plugins.
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
     * @param CommandData $commandData
     * @throws TerminusNotFoundException
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
     */
    private function doUninstallation(PluginInfo $project)
    {
        $config = $this->getConfig();
        $plugins_dir = $config->get('plugins_dir');
        $dependencies_dir = $config->get('terminus_dependencies_dir');
        // @todo Kevin should return folders to use later.
        $this->updateTerminusDependencies($dependencies_dir, $plugins_dir);
        try {
            $project_name = $project->getName();

            // First remove from terminus-dependencies.
            $command = str_replace(
                ['{dir}', '{project}',],
                [$dependencies_dir, $project_name,],
                self::UNINSTALL_COMMAND
            );
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error removing package in terminus-dependencies.',
                    []
                );
            }

            // Update terminus-dependencies composer.
            $results = $this->runComposerUpdate($dependencies_dir);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error running composer update in terminus-dependencies.',
                    []
                );
            }

            // @todo Kevin copy folders.

            $this->log()->notice('Uninstalled {project_name}.', compact('project_name'));
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }
        return $results;

    }
}
