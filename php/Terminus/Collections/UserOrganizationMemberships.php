<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;

class UserOrganizationMemberships extends TerminusCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\UserOrganizationMembership';
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

}
