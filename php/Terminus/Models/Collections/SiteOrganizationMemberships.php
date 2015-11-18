<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;

class SiteOrganizationMemberships extends TerminusCollection {
  protected $site;
  protected $workflows;

  /**
   * Adds this org as a member to the site
   *
   * @param [string] $name Name of site to add org to
   * @param [string] $role Role for supporting organization to take
   * @return [Workflow] $workflow
   **/
  public function addMember($name, $role) {
    $workflow = $this->site->workflows->create(
      'add_site_organization_membership',
      array('params' => array('organization_name' => $name, 'role' => $role))
    );
    return $workflow;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param [array] $options params to pass to url request
   * @return [TerminusModel] $this
   */
  public function fetch($options = array()) {
    if (!isset($options['paged'])) {
      $options['paged'] = true;
    }

    parent::fetch($options);
    return $this;
  }

  /**
   * Returns UUID of organization with given name
   *
   * @param [string] $name A name to search for
   * @return [SiteOrganizationMembership] $orgs[$name]
   */
  public function findByName($name) {
    $orgs = array();
    foreach ($this->models as $org_member) {
      $org = $org_member->getName();
      if ($name == $org) {
        return $org_member;
      }
    }
    return null;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'site';
    return $owner_name;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->get('id') . '/memberships/organizations';
    return $url;
  }

}
