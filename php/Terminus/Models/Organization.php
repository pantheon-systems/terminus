<?php

namespace Terminus\Models;

use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\OrganizationUserMemberships;
use Terminus\Models\Collections\Workflows;

class Organization extends TerminusModel {
  /**
   * @var OrganizationSiteMemberships
   */
  public $site_memberships;
  /**
   * @var OrganizationUserMemberships
   */
  public $user_memberships;
  /**
   * @var Workflows
   */
  public $workflows;
  /**
   * @var array
   */
  private $features;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return Organization
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $params                 = ['organization' => $this,];
    $this->site_memberships = new OrganizationSiteMemberships($params);
    $this->user_memberships = new OrganizationUserMemberships($params);
    $this->workflows        = new Workflows(['owner' => $this,]);
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
    $site_memberships = $this->site_memberships->all();
    $sites = array_combine(
      array_map(
        function($membership) {
          return $membership->site->id;
        },
        $site_memberships
      ),
      array_map(
        function($membership) {
          return $membership->site;
        },
        $site_memberships
      )
    );
    return $sites;
  }

  /**
   * Retrieves organization users
   *
   * @return User[]
   */
  public function getUsers() {
    $users = array_combine(
      array_map(
        function($membership) {
          return $membership->user->id;
        },
        $this->user_memberships->all()
      ),
      array_map(
        function($membership) {
          return $membership->user;
        },
        $this->user_memberships->all()
      )
    );
    return $users;
  }

}
