<?php

namespace Terminus\Models\Collections;

class SiteUserMemberships extends NewCollection {
  /**
   * @var Site
   */
  protected $site;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\SiteUserMembership';
  /**
   * @var bool
   */
  protected $paged = true;
  
  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return SiteUserMemberships
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url  = "sites/{$this->site->id}/memberships/users";
  }

  /**
   * Adds this user as a member to the site
   *
   * @param string $email Email of team member to add
   * @param string $role  Role to assign to the new user
   * @return Workflow
   **/
  public function create($email, $role) {
    $workflow = $this->site->workflows->create(
      'add_site_user_membership',
      ['params' => ['user_email' => $email, 'role' => $role,],]
    );
    return $workflow;
  }

  /**
   * Retrieves the membership of the given UUID or email
   *
   * @param string $id UUID or email of desired user
   * @return SiteUserMembership
   */
  public function get($id) {
    $models     = $this->models;
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
          $membership = $model;
          break;
        }
      }
    }
    return $membership;
  }

}
