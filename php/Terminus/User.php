<?php
namespace Terminus;

use \Terminus\Organization;
use \Terminus\Models\Collections\Workflows;
use \Terminus\Models\Collections\Instruments;

class User {
  static $instance;
  public $id;
  public $workflows;
  private $organizations;
  private $profile;
  private $aliases;
  private $instruments;

  public function __construct($id = null) {
    if (null===$id) {
      $this->id = Session::getValue('user_uuid');
    } else {
      $this->id = $id;
    }

    $this->workflows = new Workflows(array('owner' => $this, 'owner_type' => 'user'));
    $this->getProfile();
    self::$instance = $this;
    return $this;
  }

  static function instance($id = null) {
    if (self::$instance) {
      new self($id);
    }
    return self::$instance;
  }

  public function getProfile() {
    if (!$this->profile) {
      $path = 'profile';
      $method = 'GET';
      $response = \TerminusCommand::request('users', $this->id, $path, $method);
      $this->profile = $response['data'];
    }
    return $this->profile;
  }

  public function getAliases() {
    if (!$this->aliases) {
      $path = 'drush_aliases';
      $method = 'GET';
      $response = \TerminusCommand::request('users', $this->id, $path, $method);
      $this->aliases = $response['data']->drush_aliases;
    }
    return $this->aliases;
  }

  public function organizations() {
    if (!$this->organizations) {
      $path = 'organizations';
      $method = "GET";
      $response = \TerminusCommand::request('users', $this->id, $path, $method);
      $this->organizations = $response['data'];
    }
    return $this->organizations;
  }

  public function sites($organization=null) {
    if ($organization) {
      $path = sprintf("organizations/%s/memberships/sites", $organization);
    } else {
      $path = "sites";
    }
    $method = 'GET';
    $response = \TerminusCommand::request('users', $this->id, $path, $method);
    return $response['data'];
  }

  public function instruments() {
    $this->instruments = new Instruments(array('user' => $this));
    return $this->instruments;
  }

  public function get($attribute) {
    if(isset($this->$attribute)) {
      return $this->$attribute;
    }
    return null;
  }

  public static function id() {
    $user = self::instance();
    return $user->get('id');
  }
}
