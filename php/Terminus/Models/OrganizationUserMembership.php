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
    $user     = $this->get('user');
    $workflow = $this->organization->workflows->create(
      'remove_organization_user_membership',
      array(
        'params'    => array(
          'user_id' => $user->get('id')
        )
      )
    );
    return $workflow;
  }

}
