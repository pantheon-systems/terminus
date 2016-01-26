<?php

namespace Terminus\Models\Collections;

class Commits extends TerminusCollection {

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'sites/%s/environments/%s/code-log',
      $this->environment->site->get('id'),
      $this->environment->get('id')
    );
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'environment';
    return $owner_name;
  }

}
