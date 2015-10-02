<?php

namespace Terminus\Models;

use Terminus\Models\User;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\OrganizationSiteMemberships;
//use Terminus\Models\Collections\OrganizationUserMemberships;
use Terminus\Models\Collections\Workflows;

class Organization extends TerminusModel {
  protected $site_memberships;
  protected $user;
  protected $user_memberships;
  protected $workflows;

  /**
   * Object constructor
   * TODO: Implement OrganizationUserMemberships
   *
   * @param [stdClass] $attributes Attributes of this model
   * @param [array]    $options    Options to set as $this->key
   * @return [Organization] $this
   */
  public function __construct($attributes = null, $options = array()) {
    parent::__construct($attributes, $options);
    if (!isset($this->user)) {
      $this->user = new User();
    }
    $params = array(
      'organization' => $this,
      'owner'        => $this,
      'owner_type'   => 'organization',
    );
    $this->site_memberships = new OrganizationSiteMemberships($params);
    //$this->user_memberships = new OrganizationUserMemberships($params);
    $this->workflows = new Workflows($params);
  }

  /**
   * Retrieves organization sites
   *
   * @return [array] $sites An array of OrganizationSiteMember objects
   */
  public function getSites() {
    $sites = $this->site_memberships->all();
    return $sites;
  }

  /**
   * Retrieves organization users
   *
   * @return [array] $users An array of OrganizationUserMember objects
   */
  public function getUsers() {
    $users = $this->user_memberships->all();
    return $users;
  }

}
