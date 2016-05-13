<?php

namespace Terminus\Models\Collections;

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
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Commits
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->environment = $options['environment'];
    $this->url         = sprintf(
      'sites/%s/environments/%s/code-log',
      $this->environment->site->id,
      $this->environment->id
    );
  }
  
}
