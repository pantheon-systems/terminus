<?php

namespace Pantheon\Terminus\Commands\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Class DeleteCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class DeleteCommand extends SiteCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

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
        try {
            $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
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
