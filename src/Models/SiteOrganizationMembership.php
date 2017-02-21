<?php

namespace Pantheon\Terminus\Models;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\OrganizationJoinInterface;
use Pantheon\Terminus\Friends\OrganizationJoinTrait;
use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

class SiteOrganizationMembership extends TerminusModel implements ContainerAwareInterface, OrganizationJoinInterface, SiteInterface
{
    use ContainerAwareTrait;
    use OrganizationJoinTrait;
    use SiteTrait;

    public static $pretty_name = 'site-organization membership';

    /**
     * Remove membership of organization
     *
     * @return Workflow
     **/
    public function delete()
    {
        return $this->getSite()->getWorkflows()->create(
            'remove_site_organization_membership',
            ['params' => ['organization_id' => $this->id,],]
        );
    }

    /**
     * Get model data as PropertyList
     *
     * @return PropertyList
     */
    public function serialize()
    {
        $organization = $this->getOrganization();
        $site = $this->getSite();
        return [
            'org_id' => $organization->id,
            'org_name' => $organization->getName(),
            'site_id' => $site->id,
            'site_name' => $site->getName(),
        ];
    }

    /**
     * Changes the role of the given member
     *
     * @param string $role Desired role for this organization
     * @return Workflow
     */
    public function setRole($role)
    {
        return $this->getSite()->getWorkflows()->create(
            'update_site_organization_membership',
            ['params' => ['organization_id' => $this->id, 'role' => $role,],]
        );
    }
}
