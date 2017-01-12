<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Plugin
 */
class SearchCommand extends PluginBaseCommand
{
    /**
     * Search for available Terminus plugins.
     *
     * @command plugin:search
     * @aliases plugin:find plugin:locate
     *
     * @option string $keyword A search string used to query for plugins. Example: terminus plugin:search "Terminus plugin".
     *
     * @field-labels
     *   name: Name
     *   description: Description
     *
     * @return RowOfFields
     */
    public function search($keyword = '')
    {
        // Check for minimum plugin command requirements.
        $this->checkRequirements();

        if (empty($keyword)) {
            $message = "Usage: terminus plugin:<search|find|locate> <string>";
            throw new TerminusNotFoundException($message);
        }

        // @TODO: Limit the search to include only Packagist projects with versions
        //        compatible with the currently installed Terminus version.

        // @TODO: Bonus: Add the ability to search and prompt to install new plugins.

        $results = [];
        exec("composer search -t terminus-plugin {$keyword}", $messages);
        foreach ($messages as $message) {
            if (stripos($message, 'terminus') !== false && stripos($message, 'plugin') !== false) {
                $results[] = explode(' ', $message);
            }
        }
        $rows = [];
        if (!empty($results)) {
            foreach ($results as $result) {
                $name = array_shift($result);
                $desc = implode(' ', $result);
                $rows[] = [
                    'name'        => $name,
                    'description' => $desc,
                ];
            }
        }

        // Output the search results in table format.
        return new RowsOfFields($rows);
    }
}
