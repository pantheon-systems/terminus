<?php

namespace Terminus;

use Terminus;
use TerminusCommand;
use Terminus\Session;
use Terminus\Exceptions\TerminusException;

class Auth {
  private $logger;

  /**
   * Object constructor. Sets the logger class property.
   *
   * @return [Auth] $this
   */
  public function __construct() {
    $this->logger = Terminus::getLogger();
  }

  /**
   * Determines if user is logged in and logs the user in if they are not and
   * a refresh token is present.
   *
   * @return [boolean] True if user is logged in
   */
  public static function loggedIn() {
    if (Session::instance()->get('session', false) === false) {
      $refresh = Session::instance()->get('refresh', false);
      if (!$refresh) {
        throw new TerminusException(
          'Please log in first with `terminus auth login`.',
          array(),
          1
        );
      }
      Terminus::launchSelf('auth', array('login'), compact('refresh'));
    }
    return true;
  }

  /**
   * Execute the login based on a new refresh token
   *
   * @param [string] $token Refresh token to initiate login with
   * @return [boolean] True if login succeeded
   */
  public function logInViaRefreshToken($token) {
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'cookies' => array('Authorization: Bearer' => $token),
    );
    $logger_context = compact('token');

    $this->logger->info(
      'Logging in via refresh token {token}',
      $logger_context
    );
    $response = TerminusCommand::request(
      'auth/refresh',
      '',
      '',
      'POST',
      $options
    );
    if ($response['status_code'] != '200') {
      throw new TerminusException(
        'Login via refresh token {token} was unsuccessful.',
        $logger_context,
        1
      );
    }

    $this->setInstanceData(
      array(
        'user_uuid'           => $response['data']->id,
        'session'             => $response['data']->session,
        'session_expire_time' => 0,
        'email'               => $response['data']->email,
        'refresh'             => $token,
      )
    );
  }

  /**
   * Execute the login based on an existing session token
   *
   * @param [string] $token Session token to initiate login with
   * @return [boolean] True if login 
   */
  public function logInViaSessionToken($token = '')  {
    if ($token == '') {
      $token = Session::instance()->get('session', false);
    }
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'cookies' => array('X-Pantheon-Session' => $token),
    );

    $this->logger->info('Validating session token');
    $response = TerminusCommand::request('user', '', '', 'GET', $options);

    if (!$response
      || !isset($response['info']['http_code'])
      || $response['info']['http_code'] != '200'
    ) {
      $this->logger->info(
        'Session token {token} is not valid.',
        compact('token')
      );
      $refresh_token = Session::instance()->get('refresh', false);
      if ($refresh_token) {
        $this->logInViaRefreshToken($refresh_token);
      }
    }
    $this->logger->info(
      'Logged in as {email}.',
      array('email' => $response['data']->email)
    );

    $this->setInstanceData(
      array(
        'user_uuid'           => $response['data']->id,
        'session'             => $token,
        'session_expire_time' => 0,
        'email'               => $response['data']->email,
      )
    );
    return true;
  }

  /**
   * Execute the login via email/password
   *
   * @param [string] $email    Email address associated with a Pantheon account
   * @param [string] $password Password for the account
   * @return [boolean] True if login succeeded
   */
  public function logInViaUsernameAndPassword($email, $password) {
    if (!Terminus\Utils\isValidEmail($email)) {
      throw new TerminusException(
        '{email} is not a valid email address.',
        array('email' => $email),
        1
      );
    }

    $logger_context = compact('email');
    $options        = array(
      'body' => json_encode(
        array(
          'email' => $email,
          'password' => $password,
        )
      ),
      'headers' => array('Content-type' => 'application/json'),
    );

    $this->logger->info(
      'Logging in as {email}',
      $logger_context
    );
    $response = TerminusCommand::request('login', '', '', 'POST', $options);
    if ($response['status_code'] != '200') {
      throw new TerminusException(
        'Login unsuccessful for {email}',
        $logger_context,
        1
      );
    }

    $this->setInstanceData(
      array(
        'user_uuid'           => $response['data']->user_id,
        'session'             => $response['data']->session,
        'session_expire_time' => $response['data']->expires_at,
        'email'               => $email,
      )
    );
    return true;
  }

  /**
   * Logs the user out of Pantheon by destroying the session data. If the user
   * has a refresh token, it will be preserved.
   *
   * @return [boolean] Always true
   */
  public function logOut() {
    $data = (array)Session::instance()->getData();
    if (isset($data['refresh'])) {
      $refresh = array('refresh' => $data['refresh']);
    }
    $this->log()->info('Logging out of Pantheon.');
    $this->cache->remove('session');
    if (isset($refresh)) {
      $this->setInstanceData($refresh);      
    }
    return true;
  }

  /**
   * Merges the session data with existing data and saves it.
   *
   * @param [array] $session Session data to save
   * @return [boolean] Always true
   */
  private function setInstanceData($session) {
    $data = (array)Session::instance()->getData();
    if (isset($data['data'])) {
      unset($data['data']);
    }
    $full_session = array_merge($data, $session);
    Session::instance()->setData($full_session);
    return true;
  }

}
