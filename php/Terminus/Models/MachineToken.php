<?php

namespace Terminus\Models;

class MachineToken extends NewModel {

  /**
   * Deletes machine token
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      sprintf(
        'users/%s/machine_tokens/%s',
        $this->collection->user->id,
        $this->id
      ),
      ['method' => 'delete',]
    );
    return $response;
  }

}
