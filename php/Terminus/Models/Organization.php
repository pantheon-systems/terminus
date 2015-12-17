<?php

namespace Terminus\Models;

use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\OrganizationUserMemberships;
use Terminus\Models\Collections\Workflows;

class Organization extends TerminusModel {
  /**
   * @var OrganizationSiteMemberships
   */
  protected $site_memberships;
  /**
   * @var User
   */
  protected $user;
  /**
   * @var OrganizationUserMemberships
   */
  protected $user_memberships;
  /**
   * @var Workflows
   */
  protected $workflows;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = array()) {
    parent::__construct($attributes, $options);
    if (!isset($this->user)) {
      $this->user = new User();
    }
    $this->site_memberships = new OrganizationSiteMemberships(
      array(
        'organization' => $this,
        'owner'        => $this,
        'owner_type'   => 'organization',
      )
    );
    $this->user_memberships = new OrganizationUserMemberships(
      array(
        'organization' => $this,
        'owner'        => $this,
        'owner_type'   => 'organization',
      )
    );
    //$this->user_memberships = new OrganizationUserMemberships($params);
    $this->workflows = new Workflows(
      array(
        'owner'      => $this,
        'owner_type' => 'organization',
      )
    );
  }

  /**
   * Retrieves organization sites
   *
   * @return OrganizationSiteMembership[]
   */
  public function getSites() {
    $sites = $this->site_memberships->all();
    return $sites;
  }

  /**
   * Retrieves organization users
   *
   * @return OrganizationUserMembership[]
   */
  public function getUsers() {
    $users = $this->user_memberships->all();
    return $users;
  }

}
