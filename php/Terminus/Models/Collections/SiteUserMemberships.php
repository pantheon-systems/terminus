<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\Site;
use Terminus\Models\SiteUserMembership;
use Terminus\Models\Workflow;

class SiteUserMemberships extends TerminusCollection {
  /**
   * @var Site
   */
  protected $site;

  /**
   * Adds this user as a member to the site
   *
   * @param string $email Email of team member to add
   * @param string $role  Role to assign to the new user
   * @return Workflow
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
   * @param array $options params to pass to url request
   * @return SiteUserMemberships
   */
  public function fetch(array $options = array()) {
    if (!isset($options['paged'])) {
      $options['paged'] = true;
    }
    parent::fetch($options);
    return $this;
  }

  /**
   * Retrieves the membership of the given UUID or email
   *
   * @param string $id UUID or email of desired user
   * @return SiteUserMembership
   * @throws TerminusException
   */
  public function get($id) {
    $models     = $this->getMembers();
    $membership = null;
    if (isset($models[$id])) {
      $membership = $models[$id];
    } else {
      foreach ($models as $model) {
        $userdata = $model->get('user');
        if (is_object($userdata)
          && property_exists($userdata, 'email')
          && ($userdata->email == $id)
        ) {
          return $model;
        }
      }
    }
    throw new TerminusException(
      'Cannot find site user with the name "{id}"',
      compact('id'),
      1
    );
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->get('id') . '/memberships/users';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    return 'site';
  }

}
