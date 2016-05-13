<?php

namespace Terminus\Models;

use Terminus\Exceptions\TerminusException;

class Workflow extends TerminusModel {

  /**
   * Give the URL for collection data fetching
   *
   * @return string $url URL to use in fetch query
   */
  public function getFetchUrl() {
    $url = '';
    switch ($this->getOwnerName()) {
      case 'user':
        $url = sprintf(
          'users/%s/workflows/%s',
          $this->owner->id,
          $this->id
        );
          break;
      case 'site':
        $url = sprintf(
          'sites/%s/workflows/%s',
          $this->owner->id,
          $this->id
        );
          break;
      case 'organization':
        $url  = sprintf(
          'users/%s/organizations/%s/workflows/%s',
          $this->owner->user->id,
          $this->owner->id,
          $this->id
        );
          break;
    }
    return $url;
  }

  /**
   * Re-fetches workflow data hydrated with logs
   *
   * @return Workflow
   */
  public function fetchWithLogs() {
    $options = ['params' => ['query' => ['hydrate' => 'operations_with_logs']]];
    $this->fetch($options);
    return $this;
  }

  /**
   * Returns the status of this workflow
   *
   * @return string
   */
  public function getStatus() {
    $status = 'running';
    if ($this->isFinished()) {
      $status = 'failed';
      if ($this->isSuccessful()) {
        $status = 'succeeded';
      }
    }
    return $status;
  }

  /**
   * Detects if the workflow has finished
   *
   * @return bool True if workflow has finished
   */
  public function isFinished() {
    $is_finished = (boolean)$this->get('result');
    return $is_finished;
  }

  /**
   * Detects if the workflow was successful
   *
   * @return bool True if workflow succeeded
   */
  public function isSuccessful() {
    $is_successful = ($this->get('result') == 'succeeded');
    return $is_successful;
  }

  /**
   * Returns a list of WorkflowOperations for this workflow
   *
   * @return WorkflowOperation[]
   */
  public function operations() {
    $operations_data = [];
    if (is_array($this->get('operations'))) {
      $operations_data = $this->get('operations');
    }

    $operations = [];
    foreach ($operations_data as $operation_data) {
      $operations[] = new WorkflowOperation($operation_data);
    }

    return $operations;
  }

  /**
   * Formats workflow object into an associative array for output
   *
   * @return array Associative array of data for output
   */
  public function serialize() {
    $user = 'Pantheon';
    if (isset($this->get('user')->email)) {
      $user = $this->get('user')->email;
    }
    if ($this->get('total_time')) {
      $elapsed_time = $this->get('total_time');
    } else {
      $elapsed_time = time() - $this->get('created_at');
    }

    $operations_data = [];
    foreach ($this->operations() as $operation) {
      $operations_data[] = $operation->serialize();
    }

    $data = [
      'id'             => $this->id,
      'env'            => $this->get('environment'),
      'workflow'       => $this->get('description'),
      'user'           => $user,
      'status'         => $this->getStatus(),
      'time'           => sprintf("%ds", $elapsed_time),
      'operations'     => $operations_data
    ];

    return $data;
  }

  /**
   * Waits on this workflow to finish
   *
   * @return Workflow|void
   * @throws TerminusException
   */
  public function wait() {
    while (!$this->isFinished()) {
      $this->fetch();
      sleep(3);
      /**
       * TODO: Output this to stdout so that it doesn't get mixed with any
       *   actual output. We can't use the logger here because that might be
       *   redirected to a log file where each line is timestamped.
       */
      fwrite(STDERR, '.');
    }
    echo "\n";
    if ($this->isSuccessful()) {
      return $this;
    } else {
      $final_task = $this->get('final_task');
      if (($final_task != null) && !empty($final_task->messages)) {
        foreach ($final_task->messages as $data => $message) {
          if (!is_string($message->message)) {
            $message->message = print_r($message->message, true);
          }
          throw new TerminusException((string)$message->message);
        }
      }
    }
  }

  /**
   * Gets name of the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = strtolower(
      str_replace(
        ['Terminus\\', 'Models\\',],
        '',
        get_class($this->owner)
      )
    );
    return $owner_name;
  }

}
