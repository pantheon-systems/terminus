<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Site\Org
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Add a supporting organization to a site
     *
     * @authorize
     *
     * @command site:org:add
     *
     * @param string $site The UUID or name of the site to add the organization to
     * @param string $organization The name or UUID of the organization to add to the site
     *
     * @usage terminus site:org:add <organization> <site>
     *   Adds the <organization> organization to <site> as a supporting organization.
     */
    public function add($site, $organization)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $this->getSite($site);
        
        $workflow = $site->getOrganizationMemberships()->create($organization, 'team_member');
        $this->log()->notice(
            'Adding {org} as a supporting organization to {site}.',
            ['site' => $site->getName(), 'org' => $org->getName()]
        );
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
