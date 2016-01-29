<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;
use Terminus\Session;
use Terminus\Models\User;
use Terminus\Models\UserOrganizationMembership;

class UserOrganizationMemberships extends TerminusCollection {
  protected $user;

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = array()) {
    parent::__construct($options);
    if (!isset($this->user)) {
      $this->user = Session::getUser();
    }
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return UserOrganizationMemberships
   */
  public function fetch(array $options = array()) {
    if (!isset($options['paged'])) {
      $options['paged'] = true;
    }

    parent::fetch($options);
    return $this;
  }

  /**
   * Retrieves the model of the given ID
   *
   * @param string $id ID or name of desired organization
   * @return UserOrganizationMembership $model
   */
  public function get($id) {
    $this->fetch();
    $model = null;
    if (isset($this->models[$id])) {
      $model = $this->models[$id];
    } else {
      foreach ($this->models as $model_candidate) {
        if ((isset($model_candidate->profile)
            && ($id == $model_candidate->profile->name))
          || (isset($model_candidate->get('organization')->profile)
            && $model_candidate->get('organization')->profile->name == $id)
        ) {
          $model = $model_candidate;
          break;
        }
      }
    }
    return $model;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf('users/%s/memberships/organizations', $this->user->id);
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
