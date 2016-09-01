<?php

namespace Terminus\Models;

class OrganizationUserMembership extends TerminusModel {
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var User
   */
  public $user;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = new User(
      $attributes->user,
      ['id' => $attributes->user->id, 'memberships' => [$this,],]
    );
    $this->organization = $options['collection']->organization;
  }

  /**
   * Removes a user from this organization
   *
   * @return Workflow
   */
  public function delete() {
    $workflow = $this->organization->workflows->create(
      'remove_organization_user_membership',
      ['params' => ['user_id' => $this->user->id,],]
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
      ['params' => ['user_id' => $this->user->id, 'role' => $role,],]
    );
    return $workflow;
  }

}
