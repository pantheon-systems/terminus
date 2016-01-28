<?php

namespace Terminus\Models\Collections;

class Hostnames extends TerminusCollection {

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
    $response = $this->request->simpleRequest(
      $url,
      ['method' => 'put']
    );
    return $response['data'];
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'sites/%s/environments/%s/hostnames',
      $this->environment->site->get('id'),
      $this->environment->get('id')
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
