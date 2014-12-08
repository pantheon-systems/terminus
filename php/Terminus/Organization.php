<?php
namespace Terminus;

use Terminus\User;
use Terminus\Site;

class Organization {

  public function __construct( $org ) {
    // if the org id is passed in then we need to fetch it from the user object
    if (is_string($org)) {
      $user = User::instance();
      $orgs = $user->organizations();
      $org = $orgs->$org;
    }

    // hydrate the object
    $properties = get_object_vars($org);
    foreach (get_object_vars($org) as $key => $value) {
      if(!property_exists($this,$key)) {
        $this->$key = $properties[$key];
      }
    }

    return $this;
  }

  public function addSite( Site $site ) {
    $path = sprintf("organizations/%s/sites/%s", $this->id, $site->getId());
    $method = 'PUT';
    $user = User::id();
    $response = \Terminus_Command::request('users', $user, $path, $method);
    return $response['data'];
  }

  public function removeSite( Site $site ) {
    $path = sprintf("organizations/%s/sites/%s", $this->id, $site->getId());
    $method = 'DELETE';
    $user = User::id();
    $response = \Terminus_Command::request('users', $user, $path, $method);
    return $response['data'];
  }

}
