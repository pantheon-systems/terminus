<?php
namespace Terminus;

use Terminus\User;
use Terminus\Site;
use \Terminus\Models\Collections\Workflows;
use \Terminus\Models\Collections\OrganizationSiteMemberships;

class Organization {
  public $id;
  public $user;
  public $siteMemberships;
  public $workflows;

  public function __construct( $org ) {
    // if the org id is passed in then we need to fetch it from the user object

    if (is_string($org)) {
      $this->user = new User();
      $orgs = $this->user->organizations();
      $org = $orgs->$org;
    }

    $this->id = $org->id;
    // hydrate the object
    $properties = get_object_vars($org);
    foreach (get_object_vars($org) as $key => $value) {
      if(!property_exists($this, $key)) {
        $this->$key = $properties[$key];
      }
    }

    $this->siteMemberships = new OrganizationSiteMemberships(array('organization' => $this));
    $this->workflows = new Workflows(array('owner' => $this, 'owner_type' => 'organization'));

    return $this;
  }

  public function addSite( Site $site ) {
    $workflow = $this->workflows->create('add_organization_site_membership', array(
      'params' => array(
        'site_id' => $site->getId(),
        'role' => 'team_member'
      )
    ));
    $workflow->wait();
    return $workflow;
  }

  public function removeSite( Site $site ) {
    $workflow = $this->workflows->create('remove_organization_site_membership', array(
      'params' => array(
        'site_id' => $site->getId()
      )
    ));
    $workflow->wait();
    return $workflow;
  }

  public function getSites() {
    $path = 'memberships/sites';
    $method = 'GET';
    $response = \TerminusCommand::request('organizations', $this->id, $path, $method);
    return $response['data'];
  }

  public function getId() {
    return $this->id;
  }

}
