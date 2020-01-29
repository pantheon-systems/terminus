<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteOrganizationMemberships
 * @package Pantheon\Terminus\Collections
 */
class SiteOrganizationMemberships extends SiteOwnedCollection
{
    use OrganizationsTrait;

    /**
     * @var string
     */
    protected $collected_class = SiteOrganizationMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/memberships/organizations';

    /**
     * Adds this organization as a member of the site.
     *
     * @param Organization $organization An object representing the organization
     * @param string $role Role for supporting organization to take
     * @return Workflow
     **/
    public function create(Organization $organization, $role)
    {
        return $this->getSite()->getWorkflows()->create(
            'add_site_organization_membership',
            ['params' => ['organization_name' => $organization->getLabel(), 'role' => $role,],]
        );
    }
}
