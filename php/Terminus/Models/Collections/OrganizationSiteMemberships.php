<?php

namespace Terminus\Models\Collections;

use \Terminus\Models\Collections\TerminusCollection;

class OrganizationSiteMemberships extends TerminusCollection {

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'organizations/%s/memberships/sites',
      $this->organization->id
    );
    return $url;
  }

  /**
   * Names the model-owner of this collection, false if DNE
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'organization';
    return $owner_name;
  }

}
