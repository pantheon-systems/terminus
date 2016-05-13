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
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return MachineTokens
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "users/{$this->user->id}/machine_tokens";
  }

}
