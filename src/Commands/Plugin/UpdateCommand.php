<?php

namespace Pantheon\Terminus\Commands\Plugin;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Plugin
 */
class UpdateCommand extends PluginBaseCommand
{

    /**
     * Update one or more Terminus plugins.
     *
     *
     * @command plugin:update
     * @aliases plugin:upgrade plugin:up
     *
     * @option array $plugins A list of one or more installed plugins to update
     *
     * @usage <plugin-name-1|all> [plugin-name-2] ...
     */
    public function update(array $plugins)
    {
        // @TODO: Add the ability to prompt for plugins to update.

        if (empty($plugins)) {
            $plugins = array('all');
        }

        if ($plugins[0] == 'all') {
            $plugins_dir = $this->getPluginDir();
            $plugins = $this->getPluginProjects($plugins_dir);
            if (empty($plugins[0])) {
                $message = "You have no plugins installed.";
                $this->log()->notice($message);
                return false;
            }
        }
        foreach ($plugins as $plugin) {
            $this->updatePlugin($plugin);
        }
    }
}
