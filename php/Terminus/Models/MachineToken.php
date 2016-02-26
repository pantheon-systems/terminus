<?php

namespace Terminus\Models;

class MachineToken extends TerminusModel {

  /**
   * Deletes machine token
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      'users/' . $this->user->id . '/machine_tokens/' . $this->get('id'),
      array('method' => 'delete')
    );
    return $response;
  }

}
