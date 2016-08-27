<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\OrganizationUserMembership;
use Terminus\Models\Workflow;

class OrganizationUserMemberships extends TerminusCollection {
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options To be set to $this->key
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->organization = $options['organization'];
    $this->url = "organizations/{$this->organization->id}/memberships/users";
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data  Data to feed into attributes of new model
   * @param array  $arg_options Data to make properties of the new model
   * @return void
   */
  public function add($model_data, array $arg_options = []) {
    $default_options = [
      'id'         => $model_data->id,
      'collection' => $this,
    ];
    $options         = array_merge($default_options, $arg_options);
    parent::add($model_data, $options);
  }

  /**
   * Adds a user to this organization
   *
   * @param string $uuid UUID of user user to add to this organization
   * @param string $role Role to assign to the new member
   * @return Workflow $workflow
   */
  public function create($uuid, $role) {
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
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'organization';
    return $owner_name;
  }

}
