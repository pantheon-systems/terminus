<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

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
     * @aliases self:plugins
     *
     * @field-labels
     *   name: Name
     *   description: Description
     *   version: Installed Version
     *   latest_version: Latest Version
     *   compatible_versions: Compatible With
     *   location: Location
     *
     * @return RowsOfFields
     */
    public function listPlugins()
    {
        $plugins = $this->getPluginProjects();
        asort($plugins);

        if (empty($plugins)) {
            $this->log()->warning(self::NO_PLUGINS_MESSAGE);
        }

        // Output the plugin list in table format.
        return new RowsOfFields($plugins);
    }
}
