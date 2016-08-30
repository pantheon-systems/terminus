<?php

namespace Terminus\Models;

use Terminus\Collections\Instruments;
use Terminus\Collections\MachineTokens;
use Terminus\Collections\SshKeys;
use Terminus\Collections\UserOrganizationMemberships;
use Terminus\Collections\UserSiteMemberships;
use Terminus\Collections\Workflows;

class User extends TerminusModel {
  /**
   * @var \stdClass
   * @todo Wrap this in a proper class.
   */
  public $aliases;
  /**
   * @var Instruments
   */
  public $instruments;
  /**
   * @var Instruments
   */
  public $machine_tokens;
  /**
   * @var UserOrganizationMemberships
   */
  public $org_memberships;
  /**
   * @var UserSiteMemberships
   */
  public $site_memberships;
  /**
   * @var SshKeys
   */
  public $ssh_keys;
  /**
   * @var Workflows
   */
  public $workflows;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->url = "users/{$this->id}";

    $params                 = ['user' => $this,];
    $this->instruments      = new Instruments($params);
    $this->machine_tokens   = new MachineTokens($params);
    $this->org_memberships  = new UserOrganizationMemberships($params);
    $this->site_memberships = new UserSiteMemberships($params);
    $this->ssh_keys         = new SshKeys($params);
    $this->workflows        = new Workflows($params);
  }

  /**
   * Retrieves Drush aliases for this user
   *
   * @return \stdClass
   */
  public function getAliases() {
    if (!$this->aliases) {
      $this->setAliases();
    }
    return $this->aliases;
  }

  /**
   * Retrieves organization data for this user
   *
   * @return Organization[]
   */
  public function getOrganizations() {
    $organizations = array_combine(
      array_map(
        function($membership) {
          return $membership->organization->id;
        },
        $this->org_memberships->all()
      ),
      array_map(
        function($membership) {
          return $membership->organization;
        },
        $this->org_memberships->all()
      )
    );
    return $organizations;
  }

  /**
   * Requests API data and returns an object of user site data
   *
   * @return Site[]
   */
  public function getSites() {
    $sites = array_combine(
      array_map(
        function($membership) {
          return $membership->site->id;
        },
        $this->site_memberships->all()
      ),
      array_map(
        function($membership) {
          return $membership->site;
        },
        $this->site_memberships->all()
      )
    );
    return $sites;
  }

  /**
   * Formats User object into an associative array for output
   *
   * @return array $data associative array of data for output
   */
  public function serialize() {
    $first_name = $last_name = null;
    if (isset($this->get('profile')->firstname)) {
      $first_name = $this->get('profile')->firstname;
    }
    if (isset($this->get('profile')->lastname)) {
      $last_name = $this->get('profile')->lastname;
    }

    $data = [
      'firstname' => $first_name,
      'lastname'  => $last_name,
      'email' => $this->get('email'),
      'id'  => $this->id,
    ];
    return $data;
  }

  /**
   * Requests API data and populates $this->aliases
   *
   * @return void
   */
  private function setAliases() {
    $path     = "users/{$this->id}/drush_aliases";
    $options  = ['method' => 'get',];
    $response = $this->request->request($path, $options);

    $this->aliases = $response['data']->drush_aliases;
  }

}