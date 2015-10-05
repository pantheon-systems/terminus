<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;
use Terminus\Models\User;

class UserOrganizationMemberships extends TerminusCollection {
  protected $user;

  /**
   * Object constructor
   *
   * @param [array] $options Options to set as $this->key
   * @return [TerminusModel] $this
   */
  public function __construct($options = array()) {
    parent::__construct($options);
    if (!isset($this->user)) {
      $this->user = new User();
    }
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [UserOrganizationMemberships] $this
   */
  public function fetch($paged = false) {
    parent::fetch(true);
    return $this;
  }

  /**
   * Retrieves the model of the given ID
   *
   * @param [string] $id ID or name of desired organization
   * @return [UserOrganizationMembership] $model
   */
  public function get($id) {
    $orgs    = $this->getMembers();
    $orglist = \Terminus\Helpers\Input::orglist();
    $model = null;
    if (isset($orgs[$id])) {
      $model = $this->models[$id];
    } elseif (($location = array_search($id, $orglist)) !== false) {
      $model = $this->models[$location];
    }
    return $model;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf('users/%s/memberships/organizations', $this->user->id);
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
