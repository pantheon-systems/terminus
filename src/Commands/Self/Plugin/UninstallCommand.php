<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Manage Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class UninstallCommand extends PluginBaseCommand
{
    /**
     * Remove one or more Terminus plugins.
     *
     * @command self:plugin:uninstall
     * @aliases self:plugin:remove self:plugin:rm self:plugin:delete
     *
     * @option array $plugins A list of one or more installed plugins to remove
     *
     * @usage <plugin-name-1> [plugin-name-2] ...
     */
    public function uninstall(array $plugins)
    {
        // @TODO: Add the ability to prompt for plugins to remove.

        if (empty($plugins)) {
            $message = "Usage: terminus plugin:<uninstall|remove|delete>";
            $message .= " <plugin-name-1> [plugin-name-2] ...";
            throw new TerminusNotFoundException($message);
        }

        foreach ($plugins as $plugin) {
            $plugin_dir = $this->getPluginDir($plugin);
            if (!is_dir("$plugin_dir")) {
                $message = "{$plugin} is not installed.";
                $this->log()->error($message);
            } else {
                exec("rm -rf \"$plugin_dir\"", $messages);
                foreach ($messages as $message) {
                    $this->log()->notice($message);
                }
                $message = "{$plugin} was removed successfully.";
                $this->log()->notice($message);
            }
        }
    }
}
