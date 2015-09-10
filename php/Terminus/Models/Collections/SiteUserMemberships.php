<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;

class SiteUserMemberships extends TerminusCollection {
  protected $site;

  /**
   * Adds this user as a member to the site
   *
   * @param [string] $email Email of team member to add
   * @param [string] $role  Role to assign to the new user
   * @return [workflow] $workflow
   **/
  public function addMember($email, $role) {
    $workflow = $this->site->workflows->create(
      'add_site_user_membership',
      array('params' => array('user_email' => $email, 'role' => $role))
    );
    return $workflow;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [SiteUserMemberships] $this
   */
  public function fetch($paged = false) {
    parent::fetch(true);
    return $this;
  }

  /**
   * Retrieves the membership of the given UUID or email
   *
   * @param [string] $id UUID or email of desired user
   * @return [SiteUserMembership] $membership
   */
  public function get($id) {
    $models     = $this->getMembers();
    $membership = null;
    if (isset($models[$id])) {
      $membership = $models[$id];
    } else {
      foreach ($models as $model) {
        $userdata = $model->get('user');
        if ($userdata->email == $id) {
          $membership = $model;
          continue;
        }
      }
    }
    if ($membership == null) {
      throw new \Exception(
        sprintf('Cannot find site user with the name "%s"', $id)
      );
    }
    return $membership;
  }

  /**
   * Retrieves and fills in team member data
   *
   * @return [SiteUserMemberships] $this
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->get('id') . '/memberships/users';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    return 'site';
  }

}
