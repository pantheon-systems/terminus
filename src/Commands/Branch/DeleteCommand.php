<?php

namespace Pantheon\Terminus\Commands\Branch;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class DeleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Delete a git branch from a site.
     *
     * @authorized
     *
     * @command branch:delete
     *
     * @param string $site_id The name of the site to delete the branch from.
     * @param string $branch_id The name of the branch to delete
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function deleteBranch($site_id, $branch_id)
    {
        $site = $this->getSite($site_id);

        if (in_array($branch_id, ['master', 'live', 'test'])) {
            throw new TerminusException('You cannot delete the {branch_id} branch.', compact('branch_id'));
        }
        $branch = $site->getBranches()->get($branch_id);

        $workflow = $branch->delete();

        $this->log()->notice("Deleting the {branch_id} branch of the site {site_id}.", compact('branch_id', 'site_id'));
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
