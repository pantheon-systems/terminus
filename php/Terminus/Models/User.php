<?php

namespace Terminus\Models;

use Terminus\Models\Collections\Instruments;
use Terminus\Models\Collections\MachineTokens;
use Terminus\Models\Collections\SshKeys;
use Terminus\Models\Collections\UserOrganizationMemberships;
use Terminus\Models\Collections\UserSiteMemberships;
use Terminus\Models\Collections\Workflows;

class User extends TerminusModel {
  /**
   * @var UserOrganizationMemberships
   */
  public $org_memberships;
  /**
   * @var UserSiteMemberships
   */
  public $site_memberships;
  /**
   * @var Instruments
   */
  protected $instruments;
  /**
   * @var Instruments
   */
  protected $machine_tokens;
  /**
   * @var SshKeys
   */
  protected $ssh_keys;
  /**
   * @var Workflows
   */
  protected $workflows;
  /**
   * @var \stdClass
   * @todo Wrap this in a proper class.
   */
  private $aliases;
  /**
   * @var \stdClass
   * @todo Wrap this in a proper class.
   */
  private $profile;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = array()) {
    parent::__construct($attributes, $options);

    $this->id = $this->get('id');
    if (isset($attributes->profile)) {
      $this->profile = $attributes->profile;
    }
    $params                 = ['user' => $this,];
    $this->workflows        = new Workflows(['owner' => $this,]);
    $this->instruments      = new Instruments($params);
    $this->machine_tokens   = new MachineTokens($params);
    $this->ssh_keys         = new SshKeys($params);
    $this->org_memberships  = new UserOrganizationMemberships($params);
    $this->site_memberships = new UserSiteMemberships($params);
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf('users/%s', $this->id);
    return $url;
  }

  /**
   * Modify response data between fetch and assignment
   *
   * @param [object] $data attributes received from API response
   * @return [object] $data
   */
  public function parseAttributes($data) {
    if (isset($data->profile)) {
      $this->profile = $data->profile;
    }
    return $data;
  }

  /**
   * Retrieves drush aliases for this user
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
    $this->org_memberships->fetch();
    $organizations = array_combine(
      array_map(
        function($membership) {return $membership->organization->id;},
        $this->org_memberships->all()
      ),
      array_map(
        function($membership) {return $membership->organization;},
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
    $this->site_memberships->fetch();
    $sites = array_combine(
      array_map(
        function($membership) {return $membership->site->id;},
        $this->site_memberships->all()
      ),
      array_map(
        function($membership) {return $membership->site;},
        $this->site_memberships->all()
      )
    );
    return $sites;
  }

  /**
   * Formats User object into an associative array for output
   *
   * @return [array] $data associative array of data for output
   */
  public function serialize() {
    $first_name = $last_name = null;
    if (isset($this->profile->firstname)) {
      $first_name = $this->profile->firstname;
    }
    if (isset($this->profile->lastname)) {
      $last_name = $this->profile->lastname;
    }

    $data = array(
      'firstname' => $first_name,
      'lastname'  => $last_name,
      'email' => $this->get('email'),
      'id'  => $this->id,
    );
    return $data;
  }

  /**
   * Requests API data and populates $this->aliases
   *
   * @return void
   */
  private function setAliases() {
    $path     = sprintf('users/%s/drush_aliases', $this->id);
    $options  = ['method' => 'get',];
    $response = $this->request->request($path, $options);

    $this->aliases = $response['data']->drush_aliases;
  }

}
