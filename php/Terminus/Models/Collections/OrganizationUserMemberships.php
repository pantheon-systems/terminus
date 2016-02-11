<?php

namespace Terminus\Models\Collections;

use Terminus\Commands\OrganizationsCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\OrganizationUserMembership;
use Terminus\Models\User;
use Terminus\Models\Workflow;

class OrganizationUserMemberships extends TerminusCollection {
  protected $organization;

  /**
   * Adds a user to this organization
   *
   * @param string $uuid UUID of user user to add to this organization
   * @param string $role Role to assign to the new member
   * @return Workflow $workflow
   */
  public function addMember($uuid, $role) {
    $workflow = $this->organization->workflows->create(
      'add_organization_user_membership',
      ['params' => ['user_email' => $uuid, 'role' => $role,]]
    );
    return $workflow;
  }

  /**
   * Retrieves models by either user ID, email address, or full name
   *
   * @param string $id Either a user ID, email address, or full name
   * @return OrganizationUserMembership
   * @throws TerminusException
   */
  public function get($id) {
    $models = $this->getMembers();
    if (isset($models[$id])) {
      return $models[$id];
    }
    foreach ($models as $model) {
      $user_data = $model->get('user');
      if (in_array($id, [$user_data->email, $user_data->profile->full_name])) {
        return $model;
      }
    }
    throw new TerminusException(
      'An organization member idenfitied by "{id}" could not be found.',
      compact('id'),
      1
    );
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'organizations/%s/memberships/users',
      $this->organization->id
    );
    return $url;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return OrganizationUserMemberships
   */
  public function fetch(array $options = array()) {
    if (!isset($options['paged'])) {
      $options['paged'] = true;
    }

    parent::fetch($options);
    return $this;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'organization';
    return $owner_name;
  }

}
