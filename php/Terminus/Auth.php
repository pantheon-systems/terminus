<?php

namespace Terminus;

use Terminus\Session;

class Auth {

  /**
   * Returns current user
   *
   * @return [string] $user
   */
  public static function getUser() {
    $user = Session::getValue('email');
    return $user;
  }

  /**
   * Detects whether the user is logged in
   *
   * @return [boolean] $logged_in True when user is logged in
   */
  public static function isLoggedIn() {
    $logged_in = (boolean)Session::instance()->getValue('session', false);
    return $logged_in;
  }

}
