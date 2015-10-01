<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 */
 use Terminus\Request as Request;
 use Terminus\Auth;
 use Terminus\Utils;
 use Symfony\Component\DomCrawler\Crawler;
 use Guzzle\Parser\Cookie\CookieParser;
 use Terminus\Session;
 use Terminus\Internationalizer;

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
  public function login($args, $assoc_args) {
    # First try to login using a session token if provided
    if (isset($assoc_args['session'])) {
      $this->logger->info('validating');
      $data = $this->doLoginFromSessionToken($assoc_args['session']);
      if ( $data != FALSE ) {
        if (array_key_exists('debug', $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        $this->logger->info('success', array('user' => $email));
        Terminus::launch_self('art', array('fist'));
      }
      else {
        $this->logger->info('failure');
      }
      return;
    }

    # Otherwise do a normal email/password-based login
    if ( empty( $args ) ) {
      if (isset($_SERVER['TERMINUS_USER'])) {
        $email = $_SERVER['TERMINUS_USER'];
      } else {
        $email = $this->inputter->promptForInput('need_email');
      }
    }
    else {
      $email = $args[0];
    }

    if ( \Terminus\Utils\is_valid_email( $email ) ) {
      if ( !isset( $assoc_args['password'] ) ) {
        $password = $this->inputter->promptForInput('need_password');
        Terminus::line();
      }
      else {
        $password = $assoc_args['password'];
      }
      $data = $this->doLogin($email, $password);

      if ( $data != FALSE ) {
        if (array_key_exists('debug', $assoc_args)){
          $this->_debug(get_defined_vars());
        }
        $this->logger->info('success', array('user' => $email));
        Terminus::launch_self('art', array('fist'));
      }
      else {
        $this->logger->error('failure');
      }
    }
    else {
      $this->logger->error('invalid_email');
    }
  }

  /**
   * Log yourself out and remove the secret session key.
   */
  public function logout() {
    if (Auth::isLoggedIn()) {
      $this->logger->info('success');
      $this->cache->remove('session');
    } else {
      $this->logger->error('invalid');
    }
  }

  /**
   * Find out what user you are logged in as.
   */
  public function whoami() {
    if (Auth::isLoggedIn()) {
      $this->outputter->outputValue(Auth::getUser(), 'user');
    } else {
      $this->logger->error('invalid');
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
    if(!isset($response['status_code']) || ($response['status_code'] != '200')) {
      return false;
    }

    // Prepare credentials for storage.
    $data = array(
      'user_uuid' => $response['data']->user_id,
      'session' => $response['data']->session,
      'session_expire_time' => $response['data']->expires_at,
      'email' => $email,
    );
    // creates a session instance
    $this->logger->info('saving_session');
    Session::instance()->setData($data);
    return $data;
  }

  /**
   * Execute the login based on an existing session token
   *
   * @param $session_token string (required)
   * @return array
   */
  private function doLoginFromSessionToken($session_token) {
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'cookies' => array('X-Pantheon-Session' => $session_token),
    );

    # Temporarily disable the cache for this GET call
    $response = TerminusCommand::request('user', '', '', 'GET', $options);

    if ( !$response OR '200' != @$response['info']['http_code'] ) {
      $this->logger->error('invalid_token');
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
