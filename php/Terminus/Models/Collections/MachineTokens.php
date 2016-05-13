<?php

namespace Terminus\Models\Collections;

class MachineTokens extends NewCollection {
  /**
   * @var User
   */ 
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\MachineToken';

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return MachineTokens
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = $options['user'];
    $this->url  = "users/{$this->user->id}/machine_tokens";
  }

}
