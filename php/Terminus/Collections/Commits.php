<?php

namespace Terminus\Collections;

class Commits extends TerminusCollection {
  /**
   * @var Environment
   */
  public $environment;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Commit';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->environment = $options['environment'];
    $this->url = sprintf(
      'sites/%s/environments/%s/code-log',
      $this->environment->site->id,
      $this->environment->id
    );
  }

}
