<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
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
     *     name: Name
     *     status: Status
     *     description: Description
     * @return RowsOfFields
     */
    public function search($keyword)
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
             list($project,$description) = explode(' ', $message, 2);
            $status = $this->checkStatus($project);
            if (preg_match('#^[^/]*/[^/]*$#', $project)) {
                $results[] = [
                    'name' => $project,
                    'status' => $status,
                    'description' => $description,
                ];
            }
        }

        return new RowsOfFields($results);
    }

    protected function checkStatus($project)
    {
        // TODO: Keep an internal registry of approved third-party plugins.
        $approvedProjects = [];

        if (preg_match('#^pantheon-systems/#', $project)) {
            return 'Official';
        }

        if (in_array($project, $approvedProjects)) {
            return 'Approved';
        }

        return 'Unknown';
    }
}
