<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Site\Org
 */
class RemoveCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Disassociates a supporting organization from a site.
     *
     * @authorize
     *
     * @command site:org:remove
     * @aliases site:org:rm
     *
     * @param string $site Site name
     * @param string $organization Organization name or UUID
     *
     * @throws TerminusException
     *
     * @usage <site> <organization> Disassociates <organization> as a supporting organization from <site>.
     */
    public function remove($site, $organization)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $site = $this->getSite($site);

        $membership = $site->getOrganizationMemberships()->get($organization);
        $workflow = $membership->delete();
        $this->log()->notice(
            'Removing {org} as a supporting organization from {site}.',
            ['site' => $site->getName(), 'org' => $org->getName()]
        );
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
