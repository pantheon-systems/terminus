<?php

namespace Terminus\Models;

class UserOrganizationMembership extends TerminusModel {
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
   * @return UserSiteMembership
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = $options['collection']->user;
    $this->organization = new Organization(
      $attributes->organization,
      ['id' => $attributes->organization->id, 'memberships' => [$this,],]
    );
  }

}
