<?php

namespace Terminus\Models;

class OrganizationSiteMembership extends NewModel {
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
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->organization = $options['collection']->organization;
    $this->site         = new Site(
      (array)$attributes['site'],
      ['id' => $attributes['site']->id, 'memberships' => [$this,],]
    );
  }
  
  /**
   * Removes a site from this organization
   *
   * @return Workflow
   */
  public function removeMember() {
    $workflow = $this->organization->workflows->create(
      'remove_organization_site_membership',
      ['params' => ['site_id' => $this->site->id,],]
    );
    return $workflow;
  }

}
