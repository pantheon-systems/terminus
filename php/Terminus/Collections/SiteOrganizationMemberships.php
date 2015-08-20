<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\Session;
use Terminus\SiteOrganizationMembership;
use \TerminusCommand;

class SiteOrganizationMemberships {
  private $site;
  private $models = array();
  private $workflows;

  /**
   * Object constructor. Saves site object
   *
   * @return [SiteOrganizationMemberships] $this
   */
  public function __construct($options = array()) {
    $this->site = $options['site'];
  }

  /**
   * Adds this org as a member to the site
   *
   * @param [string] $site Name of site to add org to
   * @return [Workflow] $workflow
   **/
  public function add($name, $role) {
    $workflow = $this->site->workflows->create(
      'add_site_organization_membership',
      array('params' => array('organization_name' => $name, 'role' => $role))
    );
    return $workflow;
  }

  /**
   * Lists all organizational members
   *
   * @return [array] $org_memberships SiteOrganizationMembership objects for each org member
   */
  public function all() {
    $org_memberships = array_values($this->models);
    return $org_memberships;
  }

  /**
   * Retrieves organization with given UUID or name, if such exists
   *
   * @param [string] $id User UUID or name
   * @return [SiteOrganizationMembership] $this->models[$id] Org or null
   */
  public function get($id) {
    if(isset($this->models[$id])) {
      return $this->models[$id];
    } 
    return null;
  }

  /**
   * Retrieves and fills in team member data
   *
   * @return [SiteOrganizationMemberships] $this
   */
  public function fetch() {
    $results = TerminusCommand::paged_request(
      'sites/' . $this->site->get('id') . '/memberships/organizations'
    );

    foreach($results['data'] as $id => $org_membership_data) {
      $org_membership_data = (array)$org_membership_data;
      $org_membership_data['id']   = $org_membership_data['organization_id'];
      $org_membership_data['site'] = $this->site;
      $this->models[$id] = new SiteOrganizationMembership(
        $this->site,
        $org_membership_data
      );
    }

    return $this;
  }

  /**
   * Returns UUID of organization with given name
   *
   * @param [string] $email A name to search for
   * @return [SiteOrganizationMembership] $orgs[$name]
   */
  public function findByName($name) {
    $orgs = array();
    foreach($this->models as $org_member) {
      $org = $org_member->getName();
      if($org->name == $org) {
        return $org_member;
      }
    }
    return null;
  }

  /**
   * Lists IDs of all organizational members of this site
   *
   * @return [array] $ids Array of organization UUIDs
   */
  public function ids() {
    $ids = array_keys($this->models);
    return $ids;
  }
}
