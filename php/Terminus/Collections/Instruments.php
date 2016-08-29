<?php

namespace Terminus\Collections;

class Instruments extends TerminusCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Instrument';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url = "users/{$this->user->id}/instruments";
  }

}
