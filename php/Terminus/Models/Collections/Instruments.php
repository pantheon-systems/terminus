<?php

namespace Terminus\Models\Collections;

class Instruments extends TerminusCollection {

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'users/' . $this->user->get('id') . '/instruments';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'user';
    return $owner_name;
  }

}
