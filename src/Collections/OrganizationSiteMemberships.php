<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\SiteOrganizationMembership;

/**
 * Class OrganizationSiteMemberships.
 *
 * @package Pantheon\Terminus\Collections
 */
class OrganizationSiteMemberships extends OrganizationOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = OrganizationSiteMembership::class;

    /**
     * @var boolean
     */
    protected $paged = true;

    /**
     * @var string
     */
    protected $url = 'organizations/{organization_id}/memberships/sites';

    /**
     * Adds a site to this organization.
     *
     * @param \Pantheon\Terminus\Models\Site $site
     *   Site object of site to add to this organization.
     *
     * @return \Pantheon\Terminus\Models\Workflow
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create($site)
    {
        return $this->getOrganization()->getWorkflows()->create(
            'add_organization_site_membership',
            [
                'params' => [
                    'site_id' => $site->id,
                    'role' => SiteOrganizationMembership::ROLE_TEAM_MEMBER,
                ],
            ]
        );
    }
}
