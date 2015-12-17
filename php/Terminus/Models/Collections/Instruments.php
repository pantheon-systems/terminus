<?php

namespace Terminus\Models\Collections;

class Instruments extends TerminusCollection {

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'users/' . $this->user->id . '/instruments';
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
