<?php

namespace Terminus\Collections;

class UserSiteMemberships extends TerminusCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\UserSiteMembership';
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options To be set to $this->key
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "users/{$this->user->id}/memberships/sites";
  }

}