<?php

namespace Terminus\Models;

use Terminus\Models\TerminusModel;

class SiteUserMembership extends TerminusModel {
  protected $site;

  /**
   * Remove membership, either org or user
   *
   * @return Workflow
   **/
  public function removeMember() {
    $workflow = $this->site->workflows->create(
      'remove_site_user_membership',
      array('params' => array('user_id' => $this->id))
    );
    return $workflow;
  }

  /**
   * Changes the role of the given member
   *
   * @param string $role Desired role for this member
   * @return Workflow
   */
  public function setRole($role) {
    $workflow = $this->site->workflows->create(
      'update_site_user_membership',
      array('params' => array('user_id' => $this->id, 'role' => $role))
    );
    return $workflow;
  }

}
