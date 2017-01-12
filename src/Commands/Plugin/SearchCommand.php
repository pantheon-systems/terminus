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
     * @command plugin:search
     * @aliases plugin:find plugin:locate
     *
     * @option string $keyword A search string used to query for plugins. Example: terminus plugin:search "Terminus plugin".
     *
     * @return List of search results
     */
    public function search($keyword = '')
    {
        // Check for minimum plugin command requirements.
	if (!$this->commandExists('composer')) {
            $message = 'Please install composer to enable plugin management.  See https://getcomposer.org/download/.';
            throw new TerminusNotFoundException($message);
	}

        if (empty($keyword)) {
            $message = "Usage: terminus plugin:<search|find|locate> <string>";
            throw new TerminusNotFoundException($message);
        }

        // @TODO: Limit the search to include only Packagist projects with versions
        //        compatible with the currently installed Terminus version.

        // @TODO: Bonus: Add the ability to search and prompt to install new plugins.

        exec("composer search -t terminus-plugin {$keyword}", $messages);
        foreach ($messages as $message) {
            if (stripos($message, 'terminus') !== false && stripos($message, 'plugin') !== false) {
                $this->log()->notice($message);
            }
        }
    }
}
