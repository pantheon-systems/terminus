<?php

namespace Terminus\Models;

use \TerminusCommand;
use Terminus\Models\TerminusModel;

class Workflow extends TerminusModel {

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = '';
    switch ($this->getOwnerName()) {
      case 'user':
        $url = sprintf(
          'users/%s/workflows/%s',
          $this->owner->get('id'),
          $this->get('id')
        );
          break;
      case 'site':
        $url = sprintf(
          'sites/%s/workflows/%s',
          $this->owner->get('id'),
          $this->get('id')
        );
          break;
      case 'organization':
        $user = $this->get('user');
        $url  = sprintf(
          'users/%s/organizations/%s/workflows/%s',
          $user->id,
          $this->owner->get('id'),
          $this->get('id')
        );
          break;
    }
    return $url;
  }

  /**
   * Detects if the workflow has finished
   *
   * @return [boolean] $is_finished True if worklow has finished
   */
  public function isFinished() {
    $is_finished = (boolean)$this->get('result');
    return $is_finished;
  }

  /**
   * Detects if the workflow was successfsul
   *
   * @return [boolean] $is_successful True if workflow succeeded
   */
  public function isSuccessful() {
    $is_successful = ($this->get('result') == 'succeeded');
    return $is_successful;
  }

  /**
   * Detects if the workflow was successfsul
   *
   * @return [void]
   */
  public function logMessages() {
    $final_task = $this->get('final_task');
    foreach ($final_task->messages as $data => $message) {
      \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
    }
  }

  /**
   * Waits on this workflow to finish
   *
   * @return [Workflow] $this
   */
  public function wait() {
    while (!$this->isFinished()) {
      $this->fetch();
      sleep(3);
      print ".";
    }
    if ($this->isSuccessful()) {
      return $this;
    } else {
      $final_task = $this->get('final_task');
      if (($final_task != null) && !empty($final_task->messages)) {
        foreach ($final_task->messages as $data => $message) {
          \Terminus::error(
            sprintf('[%s] %s', $message->level, $message->message)
          );
          exit;
        }
      }
    }
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = strtolower(
      str_replace(
        array('Terminus\\', 'Models\\'),
        '',
        get_class($this->owner)
      )
    );
    return $owner_name;
  }

}
