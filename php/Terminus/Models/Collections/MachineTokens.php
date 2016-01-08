<?php

namespace Terminus\Models\Collections;

use Terminus\Models\MachineToken;

class MachineTokens extends TerminusCollection {
  protected $user;

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'users/' . $this->user->id . '/machine_tokens';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'user';
    return $owner_name;
  }

}
