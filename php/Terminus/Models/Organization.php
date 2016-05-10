<?php

namespace Terminus\Models;

use Terminus\Session;
use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\OrganizationUserMemberships;
use Terminus\Models\Collections\Workflows;

class Organization extends NewModel {
  /**
   * @var OrganizationSiteMemberships
   */
  public $site_memberships;
  /**
   * @var User
   */
  public $user;
  /**
   * @var OrganizationUserMemberships
   */
  public $user_memberships;
  /**
   * @var Workflows
   */
  protected $workflows;
  /**
   * @var array
   */
  private $features;

  /**
   * Object constructor
   *
   * @param array $attributes Attributes of this model
   * @param array $options    Options to set as $this->key
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    if (!isset($this->user)) {
      $this->user = Session::getUser();
    }
    $params                 = ['organization' => $this,];
    $this->site_memberships = new OrganizationSiteMemberships($params);
    $this->user_memberships = new OrganizationUserMemberships($params);
    $this->workflows        = new Workflows(
      ['owner' => $this, 'owner_type' => 'organization',]
    );
    $this->url              = "organizations/{$this->id}/features";
  }

  /**
   * Returns a specific organization feature value
   *
   * @param string $feature Feature to check
   * @return mixed|null Feature value, or null if not found
   */
  public function getFeature($feature) {
    if (!isset($this->features)) {
      $response       = $this->request->request(
        sprintf('organizations/%s/features', $this->id)
      );
      $this->features = (array)$response['data'];
    }
    if (isset($this->features[$feature])) {
      return $this->features[$feature];
    }
    return null;
  }

  /**
   * Retrieves organization sites
   *
   * @return Site[]
   */
  public function getSites() {
    $this->site_memberships->fetch();
    $site_memberships = $this->site_memberships->all();
    $sites            = [];
    foreach ($site_memberships as $membership) {
      $sites[$membership->site->id] = $membership->site;
    }
    return $sites;
  }

  /**
   * Retrieves organization users
   *
   * @return User[]
   */
  public function getUsers() {
    $this->user_memberships->fetch();
    $user_memberships = $this->user_memberships->all();
    $users            = [];
    foreach ($user_memberships as $membership) {
      $users[$membership->user->id] = $membership->user;
    }
    return $users;
  }

}
