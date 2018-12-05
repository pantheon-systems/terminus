<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Site\Team
 */
class RemoveCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Removes a user from a site's team.
     *
     * @authorize
     *
     * @command site:team:remove
     * @aliases site:team:rm
     *
     * @param string $site_id Site name
     * @param string $member Email, UUID, or full name
     *
     * @usage <site> <user> Removes <user> from <site>'s team.
     */
    public function remove($site_id, $member)
    {
        $workflow = $this->getSite($site_id)->getUserMemberships()->get($member)->delete();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
