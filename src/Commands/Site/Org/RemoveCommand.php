<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Removes a supporting organization from a site.
     *
     * @authorized
     *
     * @command site:org:remove
     * @aliases site:org:rm
     *
     * @param string $site The UUID or name of the site to be remove the organization from
     * @param string $organization The name or UUID of the organization to remove
     *
     * @throws \Terminus\Exceptions\TerminusException
     * @throws \Terminus\Exceptions\TerminusNotFoundException
     * @usage terminus site:org:remove <site> <organization>
     *   Removes <organization> as a supporting organization of <site>
     */
    public function removeOrgFromSite($site, $organization)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $this->getSite($site);

        if ($membership = $site->getOrganizationMemberships()->get($organization)) {
            $workflow = $membership->delete();
            $this->log()->notice(
                'Removing {org} as a supporting organization from {site}.',
                ['site' => $site->getName(), 'org' => $org->getName()]
            );
            while (!$workflow->checkProgress()) {
                // @TODO: Remove Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        } else {
            throw new TerminusException(
                'The organization {org} does not appear to be a supporting member of {site}',
                ['site' => $site->getName(), 'org' => $org->getName()]
            );
        }
    }
}
