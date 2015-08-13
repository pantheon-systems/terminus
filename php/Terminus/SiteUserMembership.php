<?php

namespace Terminus;
use \ReflectionClass;
use \Terminus\Request;
use \Terminus\Collections\Bindings;

class SiteUserMembership {
  private $id;
  private $attributes;
  private $site;
  private $user;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes
   * @param [array]    $options
   * @return [SiteUserMembership] $this
   */
  public function __construct($attributes, $options = array()) {
    if(!is_array($options)) {
      $options = get_object_vars($options);
    }
    foreach($options as $var_name => $value) {
      $this->$var_name = $value;
    }
    $this->attributes = $attributes;
  }

  /**
   * Returns attribute of given name
   *
   * @param [string] $attribute Name of attribute to retrieve
   * @return [mixed] $this->attributes->$attribute or null
   */
  public function get($attribute) {
    if(isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }

  /**
   * Returns user object within SiteUserMembership object
   *
   * @return [User] User associated with this SiteUserMembership
   */
  public function getUser() {
    return $this->user;
  }
  /**
   * Remove membshipship, either org or user
   *
   * @param [string] $site Name of site to add user to
   * @param [string] $uuid UUID of user to add
   * @return [Workflow] $workflow
   **/
  public function remove() {
    $workflow = $this->site->workflows->create('remove_site_user_membership', array(
      'params' => array(
        'user_id' => $this->id,
      )
    ));
    return $workflow;
  }

  /**
   * Changes the role of the given member
   * 
   * @param [string] $role  Desired role for this member
   * @return [Workflow] $workflow
   */
  public function setRole($role) {
    $workflow = $this->site->workflows->create('update_site_user_membership', array(
      'params' => array(
        'user_id' => $this->id,
        'role'    => $role,
      )
    ));
    return $workflow;
  }
}
