<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Removes Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 * @TODO Add the ability to prompt for plugins to remove.
 */
class UninstallCommand extends PluginBaseCommand
{
    const NOT_INSTALLED_MESSAGE = '{project} is not installed.';
    const SUCCESS_MESSAGE = '{project} was removed successfully.';
    const UNINSTALL_COMMAND = 'rm -rf %s';
    const USAGE_MESSAGE = 'terminus self:plugin:<uninstall|remove> <Project 1> [Project 2] ...';

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
                $messages = $this->doUninstallation($this->getPluginProject($project));
                foreach ($messages as $message) {
                    $this->log()->notice($message);
                }
                $this->log()->notice(self::SUCCESS_MESSAGE, compact('project'));
            } catch (TerminusNotFoundException $e) {
                $this->log()->error(self::NOT_INSTALLED_MESSAGE, compact('project'));
            }
        }
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:install
     */
    public function validate()
    {
        $this->checkRequirements();

        if (empty($commandData->input()->getArgument('projects'))) {
            throw new TerminusNotFoundException(self::USAGE_MESSAGE);
        }
    }

    /**
     * @param array $project Should have a location property
     * @return array $messages
     */
    private function doUninstallation($project)
    {
        exec(sprintf(self::UNINSTALL_COMMAND, $project['location']), $messages);
        return $messages;
    }
}
