<?php

namespace Terminus\Models;

use Terminus\Session;
use Terminus\Models\TerminusModel;
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
   * @var array
   */
  private $features;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = array()) {
    parent::__construct($attributes, $options);
    if (!isset($this->user)) {
      $this->user = Session::getUser();
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
    $this->workflows = new Workflows(
      array(
        'owner'      => $this,
        'owner_type' => 'organization',
      )
    );
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
        sprintf('organizations/%s/features', $this->get('id'))
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
