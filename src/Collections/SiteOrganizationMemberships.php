<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\Models\Workflow;

class SiteOrganizationMemberships extends TerminusCollection
{
    /**
     * @var Site
     */
    public $site;
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\SiteOrganizationMembership';
    /**
     * @var boolean
     */
    protected $paged = true;

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->site = $options['site'];
        $this->url = "sites/{$this->site->id}/memberships/organizations";
    }

    /**
     * Adds this org as a member to the site
     *
     * @param string $name Name of site to add org to
     * @param string $role Role for supporting organization to take
     * @return Workflow
     **/
    public function create($name, $role)
    {
        $workflow = $this->site->getWorkflows()->create(
            'add_site_organization_membership',
            ['params' => ['organization_name' => $name, 'role' => $role,],]
        );
        return $workflow;
    }

    /**
     * Returns UUID of organization with given name
     *
     * @param string $name A name to search for
     * @return SiteOrganizationMembership|null
     */
    public function findByName($name)
    {
        foreach ($this->models as $org_member) {
            $org = $org_member->getName();
            if ($name == $org) {
                return $org_member;
            }
        }
        return null;
    }

    /**
     * Returns UUID of organization with given name
     *
     * @param string $name A name to search for
     * @return SiteOrganizationMembership|null
     */
    public function getUUID($name)
    {
        foreach ($this->models as $org_member) {
            $org = $org_member->getName();
            if ($name == $org) {
                return $org_member;
            }
        }
        return null;
    }

    /**
     * Retrieves the model with organization of the given UUID or name
     *
     * @param string $id UUID or name of desired site membership instance
     * @return OrganizationSiteMembership
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        } else {
            foreach ($models as $key => $membership) {
                if (in_array($id, [$membership->getOrganization()->id, $membership->getOrganization()->getName()])) {
                    return $membership;
                }
            }
        }
        return null;
    }
}
