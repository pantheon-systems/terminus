<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 */
 use Terminus\Request as Request;
 use Terminus\Utils;
 use Symfony\Component\DomCrawler\Crawler;
 use Guzzle\Parser\Cookie\CookieParser;
 use Terminus\Session;

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
      Terminus::line( "Validating session token" );
      $data = $this->doLoginFromSessionToken($assoc_args['session']);
      if ( $data != FALSE ) {
        if (array_key_exists("debug", $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        Terminus::line( "Logged in as ". $data['email'] );
        Terminus::launch_self("art", array("fist"));
      }
      else {
        Terminus::error( "Login Failed!" );
      }
      return;
    }

    # Otherwise do a normal email/password-based login
    if ( empty( $args ) ) {
      $email = Terminus::prompt( "Your email address?", NULL );
    }
    else {
      $email = $args[0];
    }

    if ( \Terminus\Utils\is_valid_email( $email ) ) {
      if ( !isset( $assoc_args['password'] ) ) {
        exec("stty -echo");
        $password = Terminus::prompt( "Your dashboard password (input will not be shown)" );
        exec("stty echo");
        Terminus::line();
      }
      else {
        $password = $assoc_args['password'];
      }
      Terminus::line( "Logging in as $email" );
      $data = $this->doLogin($email, $password);

      if ( $data != FALSE ) {
        if (array_key_exists("debug", $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        Terminus::launch_self("art", array("fist"));
      }
      else {
        Terminus::error( "Login Failed!" );
      }
    }
    else {
      Terminus::error( "Error: invalid email address" );
    }
  }

  /**
   * Log yourself out and remove the secret session key.
   */
  public function logout() {
    Terminus::line( "Logging out of Pantheon." );
    $this->cache->remove('session');
  }

  /**
   * Find out what user you are logged in as.
   */
  public function whoami() {
    if (Session::getValue('email')) {
      Terminus::line( "You are authenticated as ". Session::getValue('email') );
    }
    else {
      Terminus::line( "You are not logged in." );
    }
  }

  private function _checkSession() {
    if ((!property_exists($this, "session")) || (!property_exists($this->session, "user_uuid"))) {
      return false;
    }
    $results = $this->terminus_request("user", $this->session->user_uuid, "profile", "GET");
    if ($results['info']['http_code'] >= 400){
      Terminus::line("Expired Session, please re-authenticate.");
      $this->cache->remove('session');
      Terminus::launch_self("auth", array("login"));
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
      \Terminus::error("[auth_error]: unsuccessful login");
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
    Terminus::set_config('nocache',TRUE);
    $response = TerminusCommand::request('user', '', '', 'GET', $options);
    Terminus::set_config('nocache',FALSE);

    if ( !$response OR '200' != @$response['info']['http_code'] ) {
      \Terminus::error("[auth_error]: session token not valid");
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

Terminus::add_command( 'auth', 'Auth_Command' );
