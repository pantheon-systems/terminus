<?php

namespace Terminus\Models;

use \Terminus\Session;
use \Terminus\Organization;
use \Terminus\Models\Collections\Workflows;
use \Terminus\Models\Collections\Instruments;
use Terminus\Models\TerminusModel;

class User extends TerminusModel {
  private $aliases;
  private $organizations;
  private $profile;

  protected $instruments;
  protected $workflows;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes Attributes of this model
   * @param [array]    $options    Options to set as $this->key
   * @return [User] $this
   */
  public function __construct($attributes, $options = array()) {
    if (!isset($options['id'])) {
      $options['id'] = Session::getValue('user_uuid');
    }
    parent::__construct($attributes, $options);

    $this->workflows   = new Workflows(
      array('owner' => $this)
    );
    $this->instruments = new Instruments(array('user' => $this));
    $this->setProfile();
  }

  /**
   * Retrieves drush aliases for this user
   *
   * @return [stdClass] $this->aliases
   */
  public function getAliases() {
    if (!$this->aliases) {
      $this->setAliases();
    }
    return $this->aliases;
  }

  /**
   * Retrieves profile data for this user
   *
   * @return [stdClass] $this->profile
   */
  public function getProfile() {
    if (!$this->profile) {
      $this->setProfile();
    }
    return $this->profile;
  }

  /**
   * Retrieves organization data for this user
   *
   * @return [stdClass] $this->organizations
   */
  public function getOrganizations() {
    if (!$this->organizations) {
      $this->setOrganizations();
    }
    return $this->organizations;
  }

  /**
   * Requests API data and returns an object of user site data
   *
   * @param [string] $organization UUID of organization to requests sites from
   * @return [stdClass] $response['data']
   */
  public function getSites($organization = null) {
    if ($organization) {
      $path = sprintf("organizations/%s/memberships/sites", $organization);
    } else {
      $path = "sites";
    }
    $method   = 'GET';
    $response = \TerminusCommand::request('users', $this->id, $path, $method);
    return $response['data'];
  }

  /**
   * Requests API data and populates $this->aliases
   *
   * @return [void]
   */
  private function setAliases() {
    $path     = 'drush_aliases';
    $method   = 'GET';
    $response = \TerminusCommand::request('users', $this->id, $path, $method);

    $this->aliases = $response['data']->drush_aliases;
  }

  /**
   * Requests API data and populates $this->organizations
   *
   * @return [void]
   */
  private function setOrganizations() {
    $path     = 'organizations';
    $method   = "GET";
    $response = \TerminusCommand::request('users', $this->id, $path, $method);

    $this->organizations = $response['data'];
  }

  /**
   * Requests API data and populates $this->profile
   *
   * @return [void]
   */
  private function setProfile() {
    $path     = 'profile';
    $method   = 'GET';
    $response = \TerminusCommand::request('users', $this->id, $path, $method);

    $this->profile = $response['data'];
  }

}
