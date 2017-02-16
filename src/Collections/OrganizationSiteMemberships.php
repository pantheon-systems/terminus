<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationSiteMembership;

/**
 * Class OrganizationSiteMemberships
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
     * Adds a site to this organization
     *
     * @param Site $site Site object of site to add to this organization
     * @return Workflow
     */
    public function create($site)
    {
        return $this->getOrganization()->getWorkflows()->create(
            'add_organization_site_membership',
            ['params' => ['site_id' => $site->id, 'role' => 'team_member',],]
        );
    }
}
