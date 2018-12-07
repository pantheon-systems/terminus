<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Site\Org
 */
class AddCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Associates a supporting organization with a site.
     *
     * @authorize
     *
     * @command site:org:add
     *
     * @param string $site Site name
     * @param string $organization Organization name or UUID
     *
     * @usage <site> <organization> Associates <organization> with <site> as a supporting organization.
     */
    public function add($site, $organization)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        $site = $this->getSite($site);

        $workflow = $site->getOrganizationMemberships()->create($org, 'team_member');
        $this->log()->notice(
            'Adding {org} as a supporting organization to {site}.',
            ['site' => $site->getName(), 'org' => $org->getName(),]
        );
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
