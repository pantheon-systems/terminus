<?php

namespace Terminus\Models\Collections;

class OrganizationUserMemberships extends NewCollection {
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\OrganizationUserMembership';
  /**
   * @var bool
   */
  protected $paged = true;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return OrganizationUserMemberships
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->organization = $options['organization'];
    $this->url          = "sites/{$this->organization->id}/memberships/users";
  }

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
    $models = $this->models;
    if (isset($models[$id])) {
      return $models[$id];
    }
    foreach ($models as $model) {
      $user = $model->user;
      if (in_array(
        $id,
        [$user->get('email'), $user->get('profile')->full_name,]
      )) {
        return $model;
      }
    }
    throw new TerminusException(
      'An organization member idenfitied by "{id}" could not be found.',
      compact('id'),
      1
    );
  }

}
