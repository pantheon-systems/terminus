<?php

namespace Terminus\Models\Collections;

use stdClass;
use Terminus\Models\User;
use Terminus\Models\Collections\TerminusCollection;

class OrganizationSiteMemberships extends TerminusCollection {
  protected $user;
  protected $workflows;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param [array] $options To be set to $this->key
   * @return [TerminusCollection] $this
   */
  public function __construct($options = array()) {
    parent::__construct($options);
    $this->user      = new User(new stdClass(), array());
    $this->workflows = new Workflows(
      array('owner' => $this, 'owner_type' => 'organization')
    );
  }

  /**
   * Adds a site to this organization
   *
   * @param [Site] $site Site object of site to add to this organization
   * @return [Workflow] $workflow
   */
  public function addMember($site) {
    $workflow = $this->workflows->create(
      'add_organization_site_membership',
      array(
        'params'    => array(
          'site_id' => $site->get('id'),
          'role'    => 'team_member'
        )
      )
    );
    return $workflow;
  }

  /**
   * Retrieves the model with site of the given UUID or name
   *
   * @param [string] $id UUID or name of desired site membership instance
   * @return [Site] $model
   */
  public function get($id) {
    $models = $this->getMembers();
    $model  = null;
    if (isset($models[$id])) {
      $model = $models[$id];
    } else {
      foreach ($models as $key => $membership) {
        $site = $membership->get('site');
        if ($site->name == $id) {
          $model = $models[$key];
          continue;
        }
      }
    }
    return $model;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'organizations/%s/memberships/sites',
      $this->organization->id
    );
    return $url;
  }

  /**
   * Names the model-owner of this collection, false if DNE
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'organization';
    return $owner_name;
  }

}
