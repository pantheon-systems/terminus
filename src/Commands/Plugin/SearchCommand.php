<?php

namespace Pantheon\Terminus\Commands\Plugin;

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
     * TODO: Limit the search to include only Packagist projects with version 1.x plugins
     *
     * @command plugin:search
     * @aliases plugin:find plugin:locate
     *
     * @option string $keyword A search string used to query for plugins. Example: terminus plugin:search pantheon.
     *
     * @return List of search results
     */
    public function search($keyword = '')
    {
        if (empty($keyword)) {
            $message = "Usage: terminus plugin:<search|find|locate> <string>";
            throw new TerminusNotFoundException($message);
        }

        if ($this->commandExists('composer')) {
            exec("composer search -t terminus-plugin {$keyword}", $messages);
            foreach ($messages as $message) {
                if (stripos($message, 'terminus') !== false && stripos($message, 'plugin') !== false) {
                    $this->log()->notice($message);
                }
            }
        } else {
            $message = "In order to search for Packagist projects, you need to install Composer.";
            $this->log()->notice($message);
        }
    }
}
