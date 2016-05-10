<?php

namespace Terminus\Models;

class UserSiteMembership extends NewModel {
  
  /**
   * Object constructor
   *
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = $options['user'];
    $this->site = new Site(
      (array)$attributes['site'],
      ['id' => $attributes['site']->id, 'memberships' => [$this,],]
    );
  }

}

