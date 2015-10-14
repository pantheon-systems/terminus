<?php

/**
 * Authenticate to Pantheon and store a local secret token.
 *
 */

use Terminus\Session;
use Terminus\Utils;

class Auth_Command extends TerminusCommand {
  private $sessionid;
  private $session_cookie_name='X-Pantheon-Session';
  private $uuid;
  private $logged_in = false;


  /**
   * Log in as a user
   *
   *  ## OPTIONS
   * [<email>]
   * : Email address to log in as.
   *
   * [--password=<value>]
   * : Log in non-interactively with this password. Useful for automation.
   * [--session=<value>]
   * : Authenticate using an existing session token
   * [--debug]
   * : dump call information when logging in.
   */
  public function login( $args, $assoc_args ) {
    # First try to login using a session token if provided
    if (isset($assoc_args['session'])) {
      $this->log()->info( "Validating session token" );
      $data = $this->doLoginFromSessionToken($assoc_args['session']);
      if ( $data != FALSE ) {
        if (array_key_exists("debug", $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        $this->log()->info( "Logged in as ". $data['email'] );
        Terminus::launchSelf("art", array("fist"));
      }
      else {
        $this->failure('Login failed!');
      }
      return;
    }

    # Otherwise do a normal email/password-based login
    if ( empty( $args ) ) {
      if (isset($_SERVER['TERMINUS_USER'])) {
        $email = $_SERVER['TERMINUS_USER'];
      } else {
        $email = Terminus::prompt( "Your email address?", NULL );
      }
    }
    else {
      $email = $args[0];
    }

    if ( Utils\isValidEmail( $email ) ) {
      if ( !isset( $assoc_args['password'] ) ) {
        $password = Terminus::promptSecret('Your dashboard password (input will not be shown)');
      }
      else {
        $password = $assoc_args['password'];
      }
      $this->log()->info( "Logging in as $email" );
      $data = $this->doLogin($email, $password);

      if ( $data != FALSE ) {
        if (array_key_exists("debug", $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        Terminus::launchSelf("art", array("fist"));
      }
      else {
        $this->failure('Login Failed!');
      }
    }
    else {
      $this->failure('Invalid email address');
    }
  }

  /**
   * Log yourself out and remove the secret session key.
   */
  public function logout() {
    $this->log()->info( "Logging out of Pantheon." );
    $this->cache->remove('session');
  }

  /**
   * Find out what user you are logged in as.
   */
  public function whoami() {
    if (Session::getValue('email')) {
      $this->output()->outputValue(Session::getValue('email'), "You are authenticated as");
    }
    else {
      $this->failure('You are not logged in.');
    }
  }

  private function _checkSession() {
    if ((!property_exists($this, "session")) || (!property_exists($this->session, "user_uuid"))) {
      return false;
    }
    $results = $this->terminus_request("user", $this->session->user_uuid, "profile", "GET");
    if ($results['info']['http_code'] >= 400){
      $this->failure('Your session is expired. Please reauthenticate.');
      $this->cache->remove('session');
      Terminus::launchSelf("auth", array("login"));
      $this->whoami();
      return true;
    } else {
      return (($results['info']['http_code'] <= 199 )||($results['info']['http_code'] >= 300 ))? false : true;
    }
  }

  /**
   * Execute the login based on email,password
   *
   * @param $email string (required)
   * @param $password string (required)
   * @package Terminus
   * @version 0.04-alpha
   * @return string
   */
  private function doLogin($email,$password) {
    $options = array(
        'body' => json_encode(array(
          'email' => $email,
          'password' => $password,
        )),
        'headers' => array('Content-type'=>'application/json'),
    );

    $response = TerminusCommand::request('login','','','POST',$options);
    if($response['status_code'] != '200') {
      $this->failure('Unsuccessful login');
    }

    // Prepare credentials for storage.
    $data = array(
      'user_uuid' => $response['data']->user_id,
      'session' => $response['data']->session,
      'session_expire_time' => $response['data']->expires_at,
      'email' => $email,
    );
    // creates a session instance
    Session::instance()->setData($data);
    return $data;
  }

  /**
   * Execute the login based on an existing session token
   *
   * @param $session_token string (required)
   * @return array
   */
  private function doLoginFromSessionToken($session_token)
  {

    $options = array(
        'headers' => array('Content-type'=>'application/json'),
        'cookies' => array('X-Pantheon-Session' => $session_token),
    );

    # Temporarily disable the cache for this GET call
    $response = TerminusCommand::request('user', '', '', 'GET', $options);

    if ( !$response OR '200' != @$response['info']['http_code'] ) {
      $this->failure('Session token not valid');
    }

    // Prepare credentials for storage.
    $data = array(
      'user_uuid' => $response['data']->id,
      'session' => $session_token,
      'session_expire_time' => 0, # there is not an API to provide this for a given session token
      'email' => $response['data']->email,
    );

    // creates a session instance
    Session::instance()->setData($data);
    return $data;
  }
}

Terminus::addCommand( 'auth', 'Auth_Command' );
