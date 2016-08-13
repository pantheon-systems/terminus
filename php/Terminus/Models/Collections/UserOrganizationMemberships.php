<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;

class UserOrganizationMemberships extends TerminusCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "users/{$this->user->id}/memberships/organizations";
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data  Data to feed into attributes of new model
   * @param array  $arg_options Data to make properties of the new model
   * @return void
   */
  public function add($model_data = [], array $arg_options = []) {
    $default_options = [
      'id'         => $model_data->id,
      'collection' => $this,
    ];
    $options         = array_merge($default_options, $arg_options);
    parent::add($model_data, $options);
  }

  /**
   * Retrieves the matching organization from model members
   *
   * @param string $org ID or name of desired organization
   * @return Organization $organization
   * @throws TerminusException
   */
  public function getOrganization($org) {
    $memberships = $this->all();
    foreach ($memberships as $membership) {
      $organization = $membership->organization;
      if (in_array($org, [$organization->id, $organization->get('name'),])) {
        return $organization;
      }
    }
    throw new TerminusException(
      'This user is not a member of an organizaiton identified by {org}.',
      compact('org')
    );
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
