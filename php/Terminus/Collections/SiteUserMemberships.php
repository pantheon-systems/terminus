<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;

class SiteUserMemberships extends TerminusCollection {
  /**
   * @var Site
   */
  public $site;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\SiteUserMembership';
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url = "sites/{$this->site->id}/memberships/users";
  }

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
      ['params' => ['user_email' => $email, 'role' => $role,],]
    );
    return $workflow;
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

}
