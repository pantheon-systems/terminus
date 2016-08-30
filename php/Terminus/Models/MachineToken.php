<?php

namespace Terminus\Models;

class MachineToken extends TerminusModel {

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes, array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = $options['collection']->user;
  }

  /**
   * Deletes machine token
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      "users/{$this->user->id}/machine_tokens/{$this->id}",
      ['method' => 'delete',]
    );
    return $response;
  }

}
