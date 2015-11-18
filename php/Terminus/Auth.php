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
   * Ensures the user is logged in or errs.
   *
   * @return [boolean] Always true
   */
  public static function ensureLogin() {
    $session = Session::instance()->getData();
    $auth    = new Auth();
    if (!$auth->loggedIn()) {
      if (isset($session->refresh)) {
        $auth->logInViaMachineToken($session->refresh);
      } else {
        throw new TerminusException(
          'Please login first with `terminus auth login`',
          array(),
          1
        );
      }
    }
    return true;
  }

  /**
   * Checks to see if the current user is logged in
   *
   * @return [boolean] $is_logged_in True if the user is logged in
   */
  public function loggedIn() {
    $session      = Session::instance()->getData();
    $is_logged_in = (
      isset($session->session)
      && (Terminus::isTest() || ($session->session_expire_time >= time()))
    );
    return $is_logged_in;
  }

  /**
   * Execute the login based on a machine token
   *
   * @param [string] $token Machine token to initiate login with
   * @return [boolean] True if login succeeded
   */
  public function logInViaMachineToken($token) {
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'form_params'    => array(
        'refresh_token' => $token,
      ),
    );

    $this->logger->info('Logging in via machine token');
    $response = TerminusCommand::request(
      'auth/refresh',
      '',
      '',
      'POST',
      $options
    );

    if (!$response
      || !isset($response['status_code'])
      || ($response['status_code'] != '200')
    ) {
      throw new TerminusException(
        'The provided machine token is not valid.',
        array(),
        1
      );
    }
    $this->logger->info(
      'Logged in as {uuid}.',
      array('uuid' => $response['data']->user_id)
    );
    $data          = $response['data'];
    $data->refresh = $token;
    $this->setInstanceData($response['data']);
    return true;
  }

  /**
   * Execute the login based on an existing session token
   *
   * @param [string] $token Session token to initiate login with
   * @return [boolean] True if login succeeded
   */
  public function logInViaSessionToken($token) {
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'cookies' => array('X-Pantheon-Session' => $token),
    );
    $this->logger->info('Validating session token');
    $response = TerminusCommand::request('user', '', '', 'GET', $options);
    if (!$response
      || !isset($response['status_code'])
      || $response['status_code'] != '200'
    ) {
      throw new TerminusException(
        'The session token {token} is not valid.',
        array('token' => $token),
        1
      );
    }
    $this->logger->info(
      'Logged in as {uuid}.',
      array('uuid' => $response['data']->id)
    );
    $session = array(
      'user_uuid'           => $response['data']->id,
      'session'             => $token,
      'session_expire_time' => 0,
    );
    Session::instance()->setData($session);
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

    $logger_context = array('email' => $email);
    $options        = array(
      'form_params' => array(
        'email' => $email,
        'password' => $password,
      ),
    );

    $response = TerminusCommand::request('login', '', '', 'POST', $options);
    if ($response['status_code'] != '200') {
      throw new TerminusException(
        'Login unsuccessful for {email}',
        $logger_context,
        1
      );
    }
    $this->logger->info(
      'Logged in as {uuid}.',
      array('uuid' => $response['data']->user_id)
    );

    $this->setInstanceData($response['data']);
    return true;
  }

  /**
   * Saves the session data to a cookie
   *
   * @param [array] $data Session data to save
   * @return [boolean] Always true
   */
  private function setInstanceData($data) {
    if (!isset($data->refresh)) {
      $refresh = (array)Session::instance()->get('refresh');
    } else {
      $refresh = $data->refresh;
    }
    $session = array(
      'user_uuid'           => $data->user_id,
      'session'             => $data->session,
      'session_expire_time' => $data->expires_at,
    );
    if ($refresh && is_string($refresh)) {
      $session['refresh'] = $refresh;
    }
    Session::instance()->setData($session);
    return true;
  }

}
