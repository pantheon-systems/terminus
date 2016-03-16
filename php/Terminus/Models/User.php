<?php

namespace Terminus\Models;

use Terminus\Models\Collections\UserOrganizationMemberships;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\Instruments;
use Terminus\Models\Collections\MachineTokens;
use Terminus\Models\Collections\SshKeys;
use Terminus\Models\Collections\Workflows;
use Terminus\Session;

class User extends TerminusModel {
  /**
   * @var UserOrganizationMemberships
   */
  public $organizations;

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

    if (isset($attributes->profile)) {
      $this->profile = $attributes->profile;
    }
    $params               = ['user' => $this,];
    $this->workflows      = new Workflows(['owner' => $this,]);
    $this->instruments    = new Instruments($params);
    $this->machine_tokens = new MachineTokens($params);
    $this->ssh_keys       = new SshKeys($params);
    $this->organizations  = new UserOrganizationMemberships($params);
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
    $organizations = $this->organizations->all();
    return $organizations;
  }

  /**
   * Requests API data and returns an object of user site data
   *
   * @param string $organization UUID of organization to requests sites from,
   *   or null to fetch for all organizations.
   * @return \stdClass
   */
  public function getSites($organization = null) {
    $path = sprintf('users/%s', $this->id);
    if ($organization) {
      $path .= sprintf('/organizations/%s/memberships/sites', $organization);
    } else {
      $path .= '/sites';
    }
    $options  = ['method' => 'get',];
    $response = $this->request->request($path, $options);
    return $response['data'];
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
