<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Plugins\PluginDiscovery;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Plugin
 */
class InfoCommand extends TerminusCommand
{
    /**
     * Displays info about currently installed Terminus plugins.
     *
     * @command plugin:info
     *
     * @field-labels
     *     name: name
     *     description: description
     * @return RowsOfFields
     *
     * @usage Displays info about currently installed plugins.
     */
    public function info()
    {
        $discovery = $this->getContainer()->get(PluginDiscovery::class);
        $plugins = $discovery->discover();

        $result = [];
        foreach ($plugins as $plugin) {
            $item = $plugin->getInfo();
            $fields_to_show = ['name', 'description'];
            foreach ($fields_to_show as $k) {
                if (!in_array($k, array_keys($item))){
                    unset($item[$k]);
                }
            }
            array_push($result, $item);
        }
        return new RowsOfFields($result);
    }
}
