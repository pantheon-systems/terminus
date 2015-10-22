<?php

/**
 * Authenticate to Pantheon and store a local secret token.
 */

use Terminus\Session;
use Terminus\Utils;

class Auth_Command extends TerminusCommand {
  private $auth;
  private $logged_in = false;
  private $sessionid;
  private $session_cookie_name = 'X-Pantheon-Session';
  private $uuid;

  /**
   * Instantiates object, sets auth property
   *
   * @return [Auth_Command] $this
   */
  public function __construct() {
    parent::__construct();
    $this->auth = new \Terminus\Auth();
  }

  /**
   * Log in as a user
   *
   *  ## OPTIONS
   * [<email>]
   * : Email address to log in as.
   *
   * [--password=<value>]
   * : Log in non-interactively with this password. Useful for automation.
   *
   * [--session=<value>]
   * : Authenticate using an existing session token
   *
   * [--debug]
   * : dump call information when logging in.
   */
  public function login($args, $assoc_args) {
    // Try to login using a session token, if provided.
    if (isset($assoc_args['session'])) {
      $this->auth->logInViaSessionToken($assoc_args['session']);
    } else {
      // Otherwise, do a normal email/password-based login.
      if (empty($args)) {
        if (isset($_SERVER['TERMINUS_USER'])) {
          $email = $_SERVER['TERMINUS_USER'];
        } else {
          $email = Terminus::prompt('Your email address?', null);
        }
      } else {
        $email = $args[0];
      }

      if (isset($assoc_args['password'])) {
        $password = $assoc_args['password'];
      } else {
        $password = Terminus::promptSecret(
          'Your dashboard password (input will not be shown)'
        );
      }

      $this->auth->logInViaUsernameAndPassword($email, $password);
    }
    $this->log()->debug(get_defined_vars());
    Terminus::launchSelf('art', array('fist'));
  }

  /**
   * Log yourself out and remove the secret session key.
   */
  public function logout() {
    $this->log()->info('Logging out of Pantheon.');
    $this->cache->remove('session');
  }

  /**
   * Find out what user you are logged in as.
   */
  public function whoami() {
    if (Session::getValue('email')) {
      $this->output()->outputValue(
        Session::getValue('email'),
        'You are authenticated as'
      );
    } else {
      $this->failure('You are not logged in.');
    }
  }

}

Terminus::addCommand('auth', 'Auth_Command');
