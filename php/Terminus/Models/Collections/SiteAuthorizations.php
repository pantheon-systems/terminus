<?php

namespace Terminus\Models\Collections;

class SiteAuthorizations extends TerminusCollection {
  /**
   * @var Site
   */
  public $site;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\SiteAuthorization';

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return SiteAuthorizations
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url  = "sites/{$this->site->id}/authorizations";
  }

}
