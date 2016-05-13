<?php

namespace Terminus\Models\Collections;

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
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Instruments
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "sites/{$this->user->id}/instruments";
  }

}
