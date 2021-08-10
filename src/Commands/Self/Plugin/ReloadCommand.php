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
     * @aliases self:plugin:refresh
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
        $plugins_dir = $config->get('plugins_dir');
        $dependencies_dir = $config->get('dependencies_dir');
        $backup_dependencies_directory = $this->backupDir($dependencies_dir, 'dependencies');
        try {
            $this->ensureComposerJsonExists($plugins_dir, 'pantheon-systems/terminus-plugins');
            $this->ensureComposerJsonExists($dependencies_dir, 'pantheon-systems/terminus-dependencies');
            $this->updateTerminusDependencies($dependencies_dir, $plugins_dir);
            $this->log()->notice('Plugins reload done.');
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
            $this->restoreBackup($backup_dependencies_directory, 'dependencies');
        }
    }

}
