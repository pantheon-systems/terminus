<?php

namespace Terminus\Models;

use \ReflectionClass;
use \Terminus\Request;
use \Terminus\Models\Collections\Bindings;

class SiteUserMembership extends TerminusModel {
  protected $site;

  /**
   * Remove membshipship, either org or user
   *
   * @param [string] $site Name of site to add user to
   * @param [string] $uuid UUID of user to add
   * @return [Workflow] $workflow
   **/
  public function removeMember() {
    $workflow = $this->site->workflows->create(
      'remove_site_user_membership',
        array(
        'params' => array(
          'user_id' => $this->id,
        )
      )
    );
    return $workflow;
  }

  /**
   * Changes the role of the given member
   * 
   * @param [string] $role  Desired role for this member
   * @return [Workflow] $workflow
   */
  public function setRole($role) {
    $workflow = $this->site->workflows->create(
      'update_site_user_membership',
      array(
        'params' => array(
          'user_id' => $this->id,
          'role'    => $role,
        )
      )
    );
    return $workflow;
  }

}
