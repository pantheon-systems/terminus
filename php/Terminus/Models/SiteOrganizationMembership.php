<?php

namespace Terminus\Models;

class SiteOrganizationMembership extends TerminusModel {
  /**
   * @var Site
   */
  protected $site;
  /**
   * @var Organization
   */
  protected $organization;

  /**
   * Returns organization object within SiteOrganizationMembership object
   *
   * @return Organization
   */
  public function getOrganization() {
    if (!isset($this->organization)) {
      $this->organization = new Organization($this->id);
    }
    return $this->organization;
  }

  /**
   * Remove membership of organization
   *
   * @return Workflow
   **/
  public function removeMember() {
    $workflow = $this->site->workflows->create(
      'remove_site_organization_membership',
      array('params' => array('organization_id' => $this->id))
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
      array('params' => array('organization_id' => $this->id, 'role' => $role))
    );
    return $workflow;
  }

}
