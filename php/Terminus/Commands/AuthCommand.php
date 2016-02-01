<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Auth;
use Terminus\Session;
use Terminus\Commands\TerminusCommand;

/**
 * Authenticate to Pantheon and store a local secret token.
 *
 * @command auth
 */
class AuthCommand extends TerminusCommand {
  private $auth;

  /**
   * Instantiates object, sets auth property
   *
   * @param array $options Options to construct the command object
   * @return AuthCommand
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->auth = new Auth();
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
   * [--debug]
   * : dump call information when logging in.
   */
  public function login($args, $assoc_args) {
    if (!empty($args)) {
      $email = array_shift($args);
    }
    if (isset($assoc_args['machine-token'])
      && ($assoc_args['machine-token'] !== true)
    ) {
      // Try to log in using a machine token, if provided.
      $token_data = ['token' => $assoc_args['machine-token']];
      $this->auth->logInViaMachineToken($token_data);
    } elseif (isset($email) && !isset($assoc_args['password'])
      && $this->auth->tokenExistsForEmail($email)
    ) {
      // Try to log in using a machine token, if the account email was provided.
      $this->auth->logInViaMachineToken(compact('email'));
    } elseif (empty($args) && isset($_SERVER['TERMINUS_MACHINE_TOKEN'])) {
      // Try to log in using a machine token, if it's in the $_SERVER.
      $token_data = ['token' => $_SERVER['TERMINUS_MACHINE_TOKEN']];
      $this->auth->logInViaMachineToken($token_data);
    } elseif (isset($_SERVER['TERMINUS_USER'])
      && !isset($assoc_args['password'])
      && $this->auth->tokenExistsForEmail($_SERVER['TERMINUS_USER'])
    ) {
      // Try to log in using a machine token, if $_SERVER provides account email.
      $this->auth->logInViaMachineToken(['email' => $_SERVER['TERMINUS_USER']]);
    } elseif (!isset($email)
      && $only_token = $this->auth->getOnlySavedToken()
    ) {
      // Try to log in using a machine token, if there is only one saved token.
      $this->auth->logInViaMachineToken(['email' => $only_token['email']]);
    } else if (isset($email) && isset($assoc_args['password'])) {
      $password = $assoc_args['password'];
      $this->auth->logInViaUsernameAndPassword(
        $email,
        $assoc_args['password']
      );
    } else {
      $this->log()->info(
        "Please visit the Dashboard to generate a machine token:\n{url}",
        ['url' => Auth::getMachineTokenCreationUrl()]
      );
      exit(1);
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
