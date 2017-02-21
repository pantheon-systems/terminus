<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class SiteOrganizationMemberships
 * @package Pantheon\Terminus\Collections
 */
class SiteOrganizationMemberships extends SiteOwnedCollection
{
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
     * Adds this org as a member to the site
     *
     * @param string $name Name of site to add org to
     * @param string $role Role for supporting organization to take
     * @return Workflow
     **/
    public function create($name, $role)
    {
        return $this->getSite()->getWorkflows()->create(
            'add_site_organization_membership',
            ['params' => ['organization_name' => $name, 'role' => $role,],]
        );
    }
}
