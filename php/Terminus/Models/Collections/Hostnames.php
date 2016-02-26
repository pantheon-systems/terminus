<?php

namespace Terminus\Models\Collections;

class Hostnames extends TerminusCollection {

  /**
   * @var bool Use to hydrate the data with additional information
   */
  protected $hydrate = false;

  /**
   * Add hostname to environment
   *
   * @param string $hostname Hostname to add to environment
   * @return array
   */
  public function addHostname($hostname) {
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

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'sites/%s/environments/%s/hostnames?hydrate=%s',
      $this->environment->site->get('id'),
      $this->environment->get('id'),
      $this->hydrate
    );
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'environment';
    return $owner_name;
  }

}
