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
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->organization = $options['collection']->organization;
        $this->site = new Site(
            $attributes->site,
            ['id' => $attributes->site->id, 'memberships' => [$this,],]
        );
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
