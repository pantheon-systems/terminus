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
    'composer remove -d {dir} {project}';

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
        $dependencies_dir = $config->get('dependencies_dir');
        $this->updateTerminusDependencies($dependencies_dir, $plugins_dir);
        $backup_plugins_directory = $this->backupDir($plugin_dir, 'plugins');
        $backup_dependencies_directory = $this->backupDir($dependencies_dir, 'dependencies');
        try {
            $project_name = $project->getName();
            $command = str_replace(
                ['{dir}', '{project}',],
                [$plugins_dir, $project_name,],
                self::UNINSTALL_COMMAND
            );
            // @todo kevin: How to handle terminus-dependencies
            $results = $this->runCommand($command);
            $this->log()->notice('Uninstalled {project_name}.', compact('project_name'));
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
            // @todo Kevin restore backup?.
        }
        return $results;

    }
}
