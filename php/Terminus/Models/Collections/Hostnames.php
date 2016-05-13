<?php

namespace Terminus\Models\Collections;

class Hostnames extends NewCollection {
  /**
   * @var Environment
   */
  public $environment;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Hostname';
  /**
   * @var bool Use to hydrate the data with additional information
   */
  protected $hydrate = false;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Hostnames
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->environment = $options['environment'];
    $this->url         = sprintf(
      'sites/%s/environments/%s/hostnames?hydrate=%s',
      $this->environment->site->id,
      $this->environment->id,
      $this->hydrate
    );
  }

  /**
   * Add hostname to environment
   *
   * @param string $hostname Hostname to add to environment
   * @return array
   */
  public function create($hostname) {
    $url = sprintf(
      'sites/%s/environments/%s/hostnames/%s',
      $this->environment->site->get('id'),
      $this->environment->get('id'),
      rawurlencode($hostname)
    );
    $response = $this->request->request(
      $url,
      ['method' => 'put']
    );
    return $response['data'];
  }

  /**
   * Changes the value of the hydration property
   *
   * @param mixed $value Value to set the hydration property to
   * @return void
   */
  public function setHydration($value) {
    $this->hydrate = $value;
  }

}
