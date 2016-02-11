<?php

namespace Terminus\Models;

use Terminus\Models\Organization;

// TODO: this should inherit from TerminusModel, with an `organization` property
class OrganizationUserMembership extends Organization {

  /**
   * Removes a user from this organization
   *
   * @return Workflow
   */
  public function removeMember() {
    $workflow = $this->organization->workflows->create(
      'remove_organization_user_membership',
      ['params' => ['user_id' => $this->get('user')->id]]
    );
    return $workflow;
  }

  /**
   * Sets the user's role within this organization
   *
   * @param string $role Role for this user to take in the organization
   * @return Workflow
   */
  public function setRole($role) {
    $workflow = $this->organization->workflows->create(
      'update_organization_user_membership',
      ['params' => ['user_id' => $this->get('user')->id, 'role' => $role]]
    );
    return $workflow;
  }

}
