<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DeleteCommand.
 *
 * @package Pantheon\Terminus\Commands\Site
 */
class DeleteCommand extends SiteCommand implements SiteAwareInterface
{
    use WorkflowProcessingTrait;
    use SiteAwareTrait;

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
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function delete($site_id)
    {
        $site = $this->sites()->get($site_id);
        if (!$this->input()->getOption('yes')
            && !$this->io()->confirm(sprintf('Are you sure you want to delete %s site?', $site_id))
        ) {
            return;
        }

        $workflow = $site->delete();
        $this->processWorkflow($workflow);
        $message = $workflow->getMessage();
        $this->log()->notice($message, ['site' => $site_id]);
    }
}
