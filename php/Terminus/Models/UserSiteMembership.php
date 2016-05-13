<?php

namespace Terminus\Models;

class UserSiteMembership extends NewModel {
  
  /**
   * Object constructor
   *
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   * @return UserSiteMembership
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->site = new Site(
      (array)$attributes['site'],
      ['id' => $attributes['site']->id, 'memberships' => [$this,],]
    );
    $this->user = $options['user'];
  }

}

