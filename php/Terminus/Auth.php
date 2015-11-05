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
   * Determines if user is logged in
   *
   * @return [boolean] True if user is logged in
   */
  public static function loggedIn() {
    if (Session::instance()->getValue('session', false) === false) {
      throw new TerminusException(
        'Please login first with `terminus auth login`',
        array(),
        1
      );
    }
    return true;
  }

  /**
   * Execute the login based on an existing session token
   *
   * @param [string] $token Session token to initiate login with
   * @return [boolean] True if login succeeded
   */
  public function logInViaRefreshToken($token) {
    $options = array(
      'headers' => array('Content-type' => 'application/json'),
      'body'    => array(
        'refresh_token' => $token,
      ),
    );

    $this->logger->info('Logging in via refresh token');
    $response = TerminusCommand::request('auth/refresh', '', '', 'POST', $options);

    if (!$response
      || !isset($response['info']['http_code'])
      || $response['info']['http_code'] != '200'
    ) {
      throw new TerminusException(
        'The refresh token {token} is not valid.',
        compact('token'),
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
      'body' => array(
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
   * @param [array] $session Session data to save
   * @return [boolean] Always true
   */
  private function setInstanceData($data) {
    $session = array(
      'user_uuid'           => $data->user_id,
      'session'             => $data->session,
      'session_expire_time' => $data->expires_at,
    );
    Session::instance()->setData($session);
    return true;
  }

}
