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
  public function logInViaSessionToken($token) {
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
      throw new TerminusException(
        'The session token {token} is not valid.',
        array('token' => $token),
        1
      );
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

    $logger_context = array('email' => $email);
    $options        = array(
      'body' => json_encode(
        array(
          'email' => $email,
          'password' => $password,
        )
      ),
      'headers' => array('Content-type'=>'application/json'),
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
   * Saves the session data to a cookie
   *
   * @param [array] $session Session data to save
   * @return [boolean] Always true
   */
  private function setInstanceData($session) {
    Session::instance()->setData($session);
    return true;
  }

}
