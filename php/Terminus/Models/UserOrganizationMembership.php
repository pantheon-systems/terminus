<?php

namespace Terminus\Models;

class UserOrganizationMembership extends NewModel {
  /**
   * @var Organization
   */
  public $organization;
  
  /**
   * Object constructor
   *
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $org_options        = ['id' => $attributes['id'],];
    $this->organization = new Organization(
      (array)$attributes['organization'], 
      $org_options
    );
  }
  
}