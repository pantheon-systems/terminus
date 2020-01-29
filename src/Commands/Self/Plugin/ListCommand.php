<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Manage Terminus plugins.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class ListCommand extends PluginBaseCommand
{
    /**
     * List all installed Terminus plugins.
     *
     * @command self:plugin:list
     * @aliases self:plugins
     *
     * @field-labels
     *   name: Name
     *   description: Description
     *   version: Version
     *
     * @return RowsOfFields
     */
    public function show()
    {
        $rows = [];
        $plugins_dir = $this->getPluginDir();
        $plugins = $this->getPluginProjects($plugins_dir);
        if (!empty($plugins[0])) {
            $message = "Plugins are installed in {$plugins_dir}.";
            $this->log()->notice($message);
            foreach ($plugins as $plugin) {
                $plugin_dir = $plugins_dir . $plugin;
                if (is_dir("$plugin_dir")) {
                    $name = $plugin;
                    $description = '';
                    $version = '';
                    $composer_info = $this->getComposerInfo($plugin);
                    if (!empty($composer_info)) {
                        $project = $composer_info['name'];
                        $description = $composer_info['description'];
                        $path = explode('/', $project);
                        $name = $path[1];
                        $method = $this->getInstallMethod($plugin);
                        if ($method == 'git') {
                            $version = $this->getInstalledVersion($plugin_dir);
                        } else {
                            $version = $composer_info['extra']->terminus->{'compatible-version'};
                        }
                    }
                    $rows[] = [
                        'name'        => $name,
                        'description' => $description,
                        'version'     => $version,
                    ];
                }
            }
        }

        if (empty($rows)) {
            $this->log()->notice('You have no plugins installed.');
            return false;
        }

        $count = count($rows);
        $plural = ($count > 1) ? 's' : '';
        $message = "You have {$count} plugin{$plural} installed."
            . "Use 'terminus plugin:install <org/project>...' to add more plugins.";
        $this->log()->notice($message);
        asort($rows);

        // Output the plugin list in table format.
        return new RowsOfFields($rows);
    }
}
