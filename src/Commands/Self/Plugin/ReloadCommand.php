<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Reload Terminus plugins when terminus or other folder has been updated.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class ReloadCommand extends PluginBaseCommand
{

    /**
     * Reload Terminus plugins.
     *
     * @command self:plugin:reload
     * @aliases self:plugin:refresh plugin:reload plugin:refresh
     *
     */
    public function reload()
    {
        $this->doReload();
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:install
     * @param CommandData $commandData
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();
    }

    /**
     * @param string $project_name Name of project to be installed
     * @return array Results from the install command
     */
    private function doReload()
    {
        $config = $this->getConfig();
        try {
            $original_plugins_dir = $config->get('plugins_dir');
            $original_dependencies_dir = $this->getTerminusDependenciesDir();
            $folders = $this->updateTerminusDependencies($original_plugins_dir, $original_dependencies_dir);
            $plugins_dir = $folders['plugins_dir'];
            $dependencies_dir = $folders['dependencies_dir'];
            $this->replaceFolder($plugins_dir, $original_plugins_dir);
            $this->replaceFolder($dependencies_dir, $original_dependencies_dir);
            $this->log()->notice('Plugins reload done.');
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }
    }
}
