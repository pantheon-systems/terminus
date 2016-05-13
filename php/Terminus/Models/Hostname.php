<?php

namespace Terminus\Models;

class Hostname extends NewModel {
  /**
   * @var Environment
   */
  public $environment;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return Hostname
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->environment = $options['collection']->environment;
  }

  /**
   * Delete a hostname from an environment
   *
   * @return array
   */
  public function delete() {
    $url = sprintf(
      'sites/%s/environments/%s/hostnames/%s',
      $this->environment->site->id,
      $this->environment->id,
      rawurlencode($this->id)
    );
    $response = $this->request->request($url, ['method' => 'delete']);
    return $response['data'];
  }

}
