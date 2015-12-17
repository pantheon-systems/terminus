<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Session;
use Terminus\Commands\TerminusCommand;

/**
 * Authenticate to Pantheon and store a local secret token.
 */
class AuthCommand extends TerminusCommand {
  private $auth;

  /**
   * Instantiates object, sets auth property
   */
  public function __construct() {
    parent::__construct();
    $this->auth = new Terminus\Auth();
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
   * [--machine-token=<value>]
   * : Authenticate using an Auth0 token
   *
   * [--session=<value>]
   * : Authenticate using an existing session token
   * [--debug]
   * : dump call information when logging in.
   */
  public function login($args, $assoc_args) {
    // Try to login using a machine token, if provided.
    if (isset($assoc_args['machine-token'])
      || (empty($args) && isset($_SERVER['TERMINUS_MACHINE_TOKEN']))
    ) {
      if (isset($assoc_args['machine-token'])) {
        $token = $assoc_args['machine-token'];
      } elseif (isset($_SERVER['TERMINUS_MACHINE_TOKEN'])) {
        $token = $_SERVER['TERMINUS_MACHINE_TOKEN'];
      }
      $this->auth->logInViaMachineToken($token);
    } elseif (isset($assoc_args['session'])) {
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
    if (Session::getValue('user_uuid')) {
      $this->output()->outputValue(
        Session::getValue('user_uuid'),
        'You are authenticated as'
      );
    } else {
      $this->failure('You are not logged in.');
    }
  }

}

Terminus::addCommand('auth', 'AuthCommand');
