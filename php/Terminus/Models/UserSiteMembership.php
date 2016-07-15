<?php

namespace Terminus\Models;

class UserSiteMembership extends NewModel {
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
   * @return UserSiteMembership
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->site = new Site(
      $attributes->site,
      ['id' => $attributes->site->id, 'memberships' => [$this,],]
    );
    $this->user = $options['collection']->user;
  }

}

