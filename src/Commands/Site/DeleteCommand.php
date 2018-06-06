<?php

namespace Pantheon\Terminus\Commands\Site;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class DeleteCommand extends SiteCommand
{
    /**
     * Deletes a site from Pantheon.
     *
     * @authorize
     *
     * @command site:delete
     *
     * @param string $site_name Site name
     *
     * @usage <site> Deletes <site> from Pantheon.
     */
    public function delete($site_name)
    {
        $site = $this->getSite($site_name);

        if (!$this->confirm('Are you sure you want to delete {site}?', ['site' => $site->getName()])) {
            return;
        }

        // This is a holdover until Pantheon\Terminus\Models\Site's delete()
        // function is refactored to use workflows.
        if ($site->get('service_level') != 'free') {
            $this->log()->warning('You must downgrade {site} to free tier before deleting', ['site' => $site_name,]);
        } else {
            $site->delete();
            $this->log()->notice('Deleted {site} from Pantheon', ['site' => $site_name,]);
        }
    }
}
