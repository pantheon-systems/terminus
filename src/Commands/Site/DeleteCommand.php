<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class DeleteCommand extends SiteCommand
{
    use WorkflowProcessingTrait;

    /**
     * Deletes a site from Pantheon.
     *
     * @authorize
     *
     * @command site:delete
     *
     * @param string $site_id Site name
     *
     * @usage <site> Deletes <site> from Pantheon.
     */
    public function delete($site_id)
    {
        $site = $this->getSite($site_id);
        $site_name = $site->getName();
        if (!$this->confirm('Are you sure you want to delete {site}?', ['site' => $site_name,])) {
            return;
        }

        $workflow = $site->delete();

        // We need to query the user workflows API to watch the delete_site workflow, since the site object won't exist anymore
        $workflow->setOwnerObject($this->session()->getUser());

        try {
            $this->processWorkflow($workflow);
            $message = $workflow->getMessage();
        } catch (\Exception $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }
            $message = 'Deleted {site} from Pantheon';
        }
        $this->log()->notice($message, ['site' => $site_name,]);
    }
}
