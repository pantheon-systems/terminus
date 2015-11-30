<?php

namespace Terminus\Models;

use Terminus;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\TerminusModel;

class WorkflowOperation extends TerminusModel {

  /**
   * Formats workflow object into an associative array for output
   *
   * @return [array] $data associative array of data for output
   */
  public function serialize() {
    $data = array(
      'id'        => $this->id,
      'trigger'   => $this->get('type'),
      'operation' => $this->get('description'),
      'result'    => $this->get('result'),
      'time'      => sprintf('%ss', $this->get('time')),
    );

    if ($this->get('log_output')) {
      $data['log_output'] = $this->get('log_output');
    }

    return $data;
  }

}
