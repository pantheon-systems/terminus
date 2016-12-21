<?php

namespace Pantheon\Terminus\Commands\Site;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class DeleteCommand extends SiteCommand
{
    /**
     * Delete a site from Pantheon
     *
     * @authorize
     *
     * @command site:delete
     *
     * @param string $site_name Name of the site to delete
     * @usage <site>
     *   Deletes the site named <site> from Pantheon
     */
    public function delete($site_name)
    {
        $site = $this->getSite($site_name);
        $site->delete();
        $this->log()->notice('Deleted {site} from Pantheon', ['site' => $site_name,]);
    }
}
