<?php

namespace Pantheon\Terminus\Commands\Site\Org;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand.
 *
 * @package Pantheon\Terminus\Commands\Site\Org
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

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
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function add($site, $organization)
    {
        if ($this->isValidUuid($organization)) {
            $organizationName = $this->session()->getUser()->getOrganizationMemberships()
                ->get($organization)->getOrganization()->getName();
        } else {
            $organizationName = $organization;
        }
        $site = $this->getSite($site);

        $workflow = $site->getOrganizationMemberships()->create(
            $organizationName,
            SiteOrganizationMembership::ROLE_TEAM_MEMBER
        );
        $this->log()->notice(
            'Adding {org} as a supporting organization to {site}.',
            ['site' => $site->getName(), 'org' => $organizationName,]
        );
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }

    /**
     * Return whether given string is a valid uuid or not.
     */
    private function isValidUuid(string $uuid)
    {
        return preg_match('/[a-f0-9]{8}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{4}\-[a-f0-9]{12}/', $uuid);
    }
}
