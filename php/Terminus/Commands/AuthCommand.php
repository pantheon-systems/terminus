<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Session;
use Terminus\Commands\TerminusCommand;
use Terminus\Helpers\Input;

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
   * : Authenticates using a machine token from your dashboard. Stores the
   *   token for future use.
   *
   * [--session=<value>]
   * : Authenticate using an existing session token
   *
   * [--debug]
   * : dump call information when logging in.
   */
  public function login($args, $assoc_args) {
    if (!empty($args)) {
      $email = array_shift($args);
    }
    if (isset($assoc_args['machine-token'])) {
      // Try to log in using a machine token, if provided.
      $token_data = ['token' => $assoc_args['machine-token']];
      $this->auth->logInViaMachineToken($token_data);
    } elseif (isset($email) && $this->auth->tokenExistsForEmail($email)) {
      // Try to log in using a machine token, if the account email was provided.
      $this->auth->logInViaMachineToken(compact('email'));
    } elseif (empty($args) && isset($_SERVER['TERMINUS_MACHINE_TOKEN'])) {
      // Try to log in using a machine token, if it's in the $_SERVER.
      $token_data = ['token' => $_SERVER['TERMINUS_MACHINE_TOKEN']];
      $this->auth->logInViaMachineToken($token_data);
    } elseif (isset($_SERVER['TERMINUS_USER'])
      && $this->auth->tokenExistsForEmail($_SERVER['TERMINUS_USER'])
    ) {
      // Try to log in using a machine token, if $_SERVER provides account email.
      $this->auth->logInViaMachineToken(['email' => $_SERVER['TERMINUS_USER']]);
    } elseif (!isset($email)
      && $only_token = $this->auth->getOnlySavedToken()
    ) {
      // Try to log in using a machine token, if there is only one saved token.
      $this->auth->logInViaMachineToken($only_token);
    } elseif (isset($assoc_args['session'])) {
      // Try to log in via session token, if provided.
      $this->auth->logInViaSessionToken($assoc_args['session']);
    } else {
      // Otherwise, do a normal email/password-based login.
      if (!isset($email)) {
        if (isset($_SERVER['TERMINUS_USER'])) {
          $email = $_SERVER['TERMINUS_USER'];
        } else {
          $email = Input::prompt(['message' => 'Your email address?']);
        }
      }

      if (isset($assoc_args['password'])) {
        $password = $assoc_args['password'];
      } else {
        $password = Input::promptSecret(
          ['message' => 'Your dashboard password (input will not be shown)']
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
      $user = Session::getUser();
      $user->fetch();

      $data = $user->serialize();
      $this->output()->outputRecord($data);
    } else {
      $this->failure('You are not logged in.');
    }
  }

}

Terminus::addCommand('auth', 'AuthCommand');
