<?php

namespace Pantheon\Terminus\Commands\Org\Site;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Removes a site to an organization
     *
     * @authorized
     *
     * @command org:site:remove
     * @aliases org:site:rm
     *
     * @param string $organization The name or UUID of the organization to remove a site from
     * @param string $site The UUID or name of the site to be removed from this organization
     *
     * @usage terminus org:site:remove <organization> <site>
     *   Removes the <site> site from the <organization> organization
     */
    public function remove($organization, $site)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $membership = $org->getSiteMemberships()->get($site);
        $workflow = $membership->delete();
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site} has been removed from the {org} organization.',
            ['site' => $membership->site->get('name'), 'org' => $org->get('profile')->name,]
        );
    }
}
