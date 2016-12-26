<?php

namespace Pantheon\Terminus\Models;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

class SiteOrganizationMembership extends TerminusModel implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var Site
     */
    public $site;

    /**
     * @var \stdClass
     */
    protected $organization_data;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options to set as $this->key
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->site = $options['collection']->site;
        $this->organization_data = $attributes->organization;
    }

    /**
     * Remove membership of organization
     *
     * @return Workflow
     **/
    public function delete()
    {
        $workflow = $this->site->getWorkflows()->create(
            'remove_site_organization_membership',
            ['params' => ['organization_id' => $this->id,],]
        );
        return $workflow;
    }

    /**
     * Get model data as PropertyList
     *
     * @return PropertyList
     */
    public function serialize()
    {
        $organization = $this->getOrganization();
        $data = [
            'org_id' => $organization->id,
            'org_name' => $organization->get('profile')->name,
            'site_id' => $this->site->id,
            'site_name' => $this->site->getName(),
        ];
        return $data;
    }

    /**
     * Changes the role of the given member
     *
     * @param string $role Desired role for this organization
     * @return Workflow
     */
    public function setRole($role)
    {
        $workflow = $this->site->getWorkflows()->create(
            'update_site_organization_membership',
            ['params' => ['organization_id' => $this->id, 'role' => $role,],]
        );
        return $workflow;
    }

    /**
     * @return \Terminus\Models\Organization
     */
    public function getOrganization()
    {
        if (empty($this->organization)) {
            $this->organization = $this->getContainer()->get(Organization::class, [$this->organization_data]);
            $this->organization->memberships = [$this,];
        }
        return $this->organization;
    }

    /**
     * @return \Pantheon\Terminus\Models\Site
     */
    public function getSite()
    {
        return $this->site;
    }
}
