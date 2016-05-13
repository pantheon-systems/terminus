<?php

namespace Terminus\Models;

class UserOrganizationMembership extends NewModel {
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
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   * @return UserOrganizationMembership
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->organization = new Organization(
      (array)$attributes['organization'], 
      ['id' => $attributes['id'],];
    );
    $this->user         = $attributes['organization']->user;
  }
  
}