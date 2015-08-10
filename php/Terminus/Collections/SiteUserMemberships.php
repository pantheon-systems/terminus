<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\Session;
use Terminus\SiteUserMembership;
use \Terminus_Command;

class SiteUserMemberships {
  private $site;
  private $models = array();
  private $workflows;

  /**
   * Object constructor. Saves site object
   *
   * @return [SiteUserMemberships] $this
   */
  public function __construct($options = array()) {
    $this->site = $options['site'];
  }

  /**
   * Adds this user as a member to the site
   *
   * @param [string] $site Name of site to add user to
   * @return [workflow] $workflow
   **/
  public function add($email, $role) {
    $workflow = $this->site->workflows->create('add_site_user_membership', array(
      'params' => array(
        'user_email' => $email,
        'role'       => $role,
      )
    ));
    return $workflow;
  }

  /**
   * Lists all team emembers
   *
   * @return [array] SiteUserMembership objects for each team member
   */
  public function all() {
    $user_memberships = array_values($this->models);
    return $user_memberships;
  }

  /**
   * Retrieves team member with given UUID or email, if such exists
   *
   * @param [string] $id User UUID or email
   * @return [SiteUserMembership] $user_membership Indicated user or null
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
   * @return [SiteUserMemberships] $this
   */
  public function fetch() {
    $results = Terminus_Command::paged_request('sites/' . $this->site->get('id') . '/memberships/users');

    foreach($results['data'] as $id => $user_membership_data) {
      $user_membership_data = (array)$user_membership_data;
      $user_membership_data['id']  = $user_membership_data['user_id'];
      $user_membership_data['site'] = $this->site;
      $this->models[$id] = new SiteUserMembership(null, $user_membership_data);
    }

    return $this;
  }

  /**
   * Returns UUID of user with given email address
   *
   * @param [string] $email An email address to search for
   * @return [SiteUserMembership] $users[$email]
   */
  public function findByEmail($email) {
    $users = array();
    foreach($this->models as $user_member) {
      $user = $user_member->getUser();
      if($user->email == $email) {
        return $user_member;
      }
    }
    return null;
  }

  /**
   * Lists IDs of all team members
   *
   * @return [array] $ids Array of team member UUIDs
   */
  public function ids() {
    $ids = array_keys($this->models);
    return $ids;
  }
}
