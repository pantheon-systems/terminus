<?php

namespace Terminus\Models;

class SiteUserMembership extends NewModel {
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
   * @param array  $options    Options to set as $this->key
   * @return SiteUserMembership
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->site = $options['collection']->site;
    $this->user = new User(
      $attributes->user,
      ['id' => $attributes->user->id,]
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
      ['params' => ['user_id' => $this->user->id,],]
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
      ['params' => ['user_id' => $this->user->id, 'role' => $role,],]
    );
    return $workflow;
  }

}
