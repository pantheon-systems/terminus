<?php

namespace Terminus\Models;

class OrganizationUserMembership extends NewModel {
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
   * @param array  $options    Options to set as $this->key
   * @return OrganizationUserMembership
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->organization = $options['collection']->organization;
    $this->user         = new User(
      (array)$attributes['user'],
      ['id' => $attributes['user']->id,]
    );
  }

  /**
   * Delete a hostname from an environment
   *

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
