<?php

namespace Terminus\Models;

class SiteUserMembership extends TerminusModel {
  /**
   * @var Site
   */
  public $site;
  /**
   * @var User
   */
  public $user;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   * @return SiteUserMembership
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->site = $options['collection']->site;
    $this->user = new User(
      $attributes->user,
      ['id' => $attributes->user->id, 'memberships' => [$this,],]
    );
  }

  /**
   * Remove membership, either org or user
   *
   * @return Workflow
   **/
  public function delete() {
    $workflow = $this->site->workflows->create(
      'remove_site_user_membership',
      ['params' => ['user_id' => $this->id,],]
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
      ['params' => ['user_id' => $this->id, 'role' => $role,],]
    );
    return $workflow;
  }

}
