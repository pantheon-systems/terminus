<?php

namespace Terminus\Models;

class OrganizationSiteMembership extends TerminusModel
{
    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var Site
     */
    public $site;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->organization = $options['collection']->organization;
        $this->site = new Site($attributes->site);
        $this->site->memberships = [$this,];
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->organization->id}: {$this->organization->get('profile')->name}";
    }

    /**
     * Removes a site from this organization
     *
     * @return Workflow
     */
    public function delete()
    {
        $workflow = $this->organization->workflows->create(
            'remove_organization_site_membership',
            ['params' => ['site_id' => $this->site->id,],]
        );
        return $workflow;
    }
}
