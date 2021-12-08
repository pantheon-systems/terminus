<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\AnnotatedCommand\CommandData;

/**
 * Lists installed Terminus plugins
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class ListCommand extends PluginBaseCommand
{
    const NO_PLUGINS_MESSAGE = 'You have no plugins installed.';

    /**
     * List all installed Terminus plugins.
     *
     * @command self:plugin:list
     * @aliases self:plugins plugin:list plugin:show plugins
     *
     * @field-labels
     *   name: Name
     *   description: Description
     *   installed_version: Installed Version
     *   latest_version: Latest Version
     *   compatible_versions: Compatible With
     *
     * @return RowsOfFields
     */
    public function listPlugins()
    {
        $plugins = array_map(
            function ($plugin) {
                return [
                    'name' => $plugin->getPluginName(),
                    'description' => $plugin->getInfo()['description'],
                    'installed_version' => $plugin->getInstalledVersion(),
                    'latest_version' => $plugin->getLatestVersion(),
                    'compatible_versions' => $plugin->getCompatibleTerminusVersion(),
                ];
            },
            $this->getPluginProjects()
        );
        asort($plugins);

        if (empty($plugins)) {
            $this->log()->warning(self::NO_PLUGINS_MESSAGE);
        }

        // Output the plugin list in table format.
        return new RowsOfFields($plugins);
    }

    /**
     * Check for minimum plugin commands requirements.
     * @hook validate self:plugin:list
     * @param CommandData $commandData
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();
    }
}
