<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Models\Auth;
use Terminus\Session;

/**
 * Authenticate to Pantheon and store a local secret token.
 *
 * @command auth
 */
class AuthCommand extends TerminusCommand {

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
    $auth = new Auth();
    if (!empty($args)) {
      $email = array_shift($args);
    }
    if (isset($assoc_args['machine-token'])
      && ($assoc_args['machine-token'] !== true)
    ) {
      // Try to log in using a machine token, if provided.
      $token_data = ['token' => $assoc_args['machine-token']];
      $auth->logInViaMachineToken($token_data);
      $this->log()->info('Logging in via machine token');
    } elseif (isset($email) && !isset($assoc_args['password'])
      && $auth->tokenExistsForEmail($email)
    ) {
      // Try to log in using a machine token, if the account email was provided.
      $this->log()->info(
        'Found a machine token for "{email}".',
        compact('email')
      );
      $auth->logInViaMachineToken(compact('email'));
      $this->log()->info('Logging in via machine token');
    } elseif (empty($args) && isset($_SERVER['TERMINUS_MACHINE_TOKEN'])) {
      // Try to log in using a machine token, if it's in the $_SERVER.
      $token_data = ['token' => $_SERVER['TERMINUS_MACHINE_TOKEN']];
      $auth->logInViaMachineToken($token_data);
      $this->log()->info('Logging in via machine token');
    } elseif (isset($_SERVER['TERMINUS_USER'])
      && !isset($assoc_args['password'])
      && $auth->tokenExistsForEmail($_SERVER['TERMINUS_USER'])
    ) {
      // Try to log in using a machine token, if $_SERVER provides account email.
      $this->log()->info(
        'Found a machine token for "{email}".',
        ['email' => $_SERVER['TERMINUS_USER'],]
      );
      $auth->logInViaMachineToken(['email' => $_SERVER['TERMINUS_USER']]);
      $this->log()->info('Logging in via machine token');
    } elseif (!isset($email)
      && $only_token = $auth->getOnlySavedToken()
    ) {
      // Try to log in using a machine token, if there is only one saved token.
      $this->log()->info(
        'Found a machine token for "{email}".',
        ['email' => $only_token['email'],]
      );
      $auth->logInViaMachineToken($only_token);
      $this->log()->info('Logging in via machine token');
    } else if (isset($email) && isset($assoc_args['password'])) {
      $password = $assoc_args['password'];
      $auth->logInViaUsernameAndPassword(
        $email,
        $assoc_args['password']
      );
    } else {
      $this->log()->info(
        "Please visit the Dashboard to generate a machine token:\n{url}",
        ['url' => $auth->getMachineTokenCreationUrl()]
      );
      exit(1);
    }
    if (!isset($email)) {
      $user = Session::getUser();
      $user->fetch();
      $user_data = $user->serialize();
      $email     = $user_data['email'];
    }
    $this->log()->info('Logged in as {email}.', compact('email'));

    $this->log()->debug(get_defined_vars());
    $this->helpers->launch->launchSelf(
      ['command' => 'art', 'args' => ['fist']]
    );
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
