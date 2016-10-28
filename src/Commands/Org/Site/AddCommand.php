<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Adds a site to an organization
     *
     * @authorized
     *
     * @command org:site:add
     *
     * @param string $organization The name or UUID of the organization to add a site to
     * @param string $site The UUID or name of the site to be added to this organization
     *
     * @usage terminus org:site:add <organization> <site>
     *   Adds the <site> site to the <organization> organization
     */
    public function add($organization, $site)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $this->getSite($site);
        $workflow = $org->getSiteMemberships()->create($site);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site} has been added to the {org} organization.',
            ['site' => $site->get('name'), 'org' => $org->get('profile')->name,]
        );
    }
}
