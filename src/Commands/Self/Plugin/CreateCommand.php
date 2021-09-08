<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Creates a new Terminus plugin using Composer.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class CreateCommand extends PluginBaseCommand
{
    const USAGE_MESSAGE = 'terminus self:plugin:create <path>';
    const EXISTING_FOLDER_MESSAGE = 'Path should be a non-existing folder that will be created';
    const COMPOSER_CREATE_PROJECT = 'composer create-project pantheon-systems/terminus-plugin-example {dir}';

    /**
     * Create a new terminus plugin.
     *
     * @command self:plugin:create
     * @aliases self:plugin:new
     *
     * @param string $path Path where the plugin will be created.
     *
     * @usage <path>
     */
    public function create(string $path)
    {
        if (!file_exists($path)) {
            $results = $this->doCreate($path);
            $this->log()->notice($results['output']);
        } else {
            throw new TerminusException(self::EXISTING_FOLDER_MESSAGE);
        }
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:create
     * @param CommandData $commandData
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();

        if (empty($commandData->input()->getArgument('path'))) {
            throw new TerminusException(self::USAGE_MESSAGE);
        }
    }

    /**
     * @param string $path Path where this project will be created
     * @return array Results from the create command
     */
    private function doCreate($path)
    {
        $parent_folder = dirname($path);
        $basename = basename($path);
        $realpath = realpath($parent_folder) . '/' . $basename;
        try {
            $command = str_replace(
                ['{dir}',],
                [$realpath,],
                self::COMPOSER_CREATE_PROJECT
            );
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error creating plugin project.',
                    []
                );
            }

            $project_name = $this->getProjectNameFromPath($realpath) . ':@dev';
            return $this->installProject($project_name, $realpath);
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }
    }
}
