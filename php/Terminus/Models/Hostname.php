<?php

namespace Terminus\Models;

class Hostname extends TerminusModel {

  /**
   * Delete a hostname from an environment
   *
   * @return array
   */
  public function delete() {
    $url = sprintf(
      'sites/%s/environments/%s/hostnames/%s',
      $this->environment->site->get('id'),
      $this->environment->get('id'),
      rawurlencode($this->get('id'))
    );
    $response = $this->request->request(
      $url,
      ['method' => 'delete']
    );
    return $response['data'];
  }

}
