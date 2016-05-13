<?php

namespace Terminus\Models;

class SiteOrganizationMembership extends NewModel {
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
   * @return SiteOrganizationMembership
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->organization = new Organization(
      (array)$attributes['organization'],
      ['id' => $attributes['organization']->id]
    );
    $this->site         = $options['collection']->site;
  }

  /**
   * Remove membership of organization
   *
   * @return Workflow
   **/
  public function delete() {
    $workflow = $this->site->workflows->create(
      'remove_site_organization_membership',
      ['params' => ['organization_id' => $this->id,],]
    );
    return $workflow;
  }

  /**
   * Changes the role of the given member
   *
   * @param string $role Desired role for this organization
   * @return Workflow
   */
  public function setRole($role) {
    $workflow = $this->site->workflows->create(
      'update_site_organization_membership',
      ['params' => ['organization_id' => $this->id, 'role' => $role,],]
    );
    return $workflow;
  }

}
