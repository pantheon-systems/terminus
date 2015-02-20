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
    $workflow = new Workflow('add_organization_site_membership', 'organizations', $this);
    $workflow->setParams(array('site_id' => $site->getId(), 'role' => 'team_member'));
    $workflow->setMethod("POST");
    $workflow->start();
    $workflow->wait();
    return $workflow;
  }

  public function removeSite( Site $site ) {
    $workflow = new Workflow('remove_organization_site_membership', 'organizations', $this);
    $workflow->setParams(array('site_id' => $site->getId()));
    $workflow->setMethod("POST");
    $workflow->start();
    $workflow->wait();
    return $workflow;
  }

  public function getSites() {
    $path = 'memberships/sites';
    $method = 'GET';
    $response = \Terminus_Command::request('organizations', $this->id, $path, $method);
    return $response['data'];
  }

  public function getId() {
    return $this->id;
  }

}
