<?php

namespace Terminus\Models;

use Terminus\Caches\TokensCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\TerminusModel;
use Terminus\Request;
use Terminus\Session;
use Terminus\Utils;

class Auth extends TerminusModel {

  /**
   * @var TokensCache
   */
  private $tokens_cache;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return Auth
   */
  public function __construct($attributes = null, array $options = array()) {
    $this->tokens_cache = new TokensCache();
    parent::__construct($attributes, $options);
  }

  /**
   * Gets all email addresses for which there are saved machine tokens
   *
   * @return string[]
   */
  public function getAllSavedTokenEmails() {
    $emails = $this->tokens_cache->getAllSavedTokenEmails();
    return $emails;
  }

  /**
   * Generates the URL string for where to create a machine token
   *
   * @return string
   */
  public function getMachineTokenCreationUrl() {
    $port = '';
    if (TERMINUS_HOST == 'localhost') {
      $port = ':' . TERMINUS_PORT;
    }
    $url = vsprintf(
      '%s://%s%s/machine-token/create/%s',
      [TERMINUS_PROTOCOL, TERMINUS_HOST, $port, gethostname(),]
    );
    return $url;
  }

  /**
   * Checks to see if the current user is logged in
   *
   * @return bool True if the user is logged in
   */
  public function loggedIn() {
    $session      = Session::instance()->getData();
    $is_logged_in = (
      isset($session->session)
      && (
        Utils\isTest()
        || ($session->session_expire_time >= time())
      )
    );
    return $is_logged_in;
  }

  /**
   * Execute the login based on a machine token
   *
   * @param string[] $args Elements as follow:
   *   string token Machine token to initiate login with
   *   string email Email address to locate token with
   * @return bool True if login succeeded
   * @throws TerminusException
   */
  public function logInViaMachineToken($args) {
    if (isset($args['token'])) {
      $token = $args['token'];
    } elseif (isset($args['email'])) {
      $token = $this->tokens_cache->findByEmail($args['email'])['token'];
      if (!$token) {
        throw new TerminusException(
          'No machine token for "{email}" found.',
          compact('email'),
          1
        );
      }
    }
    $options = [
      'form_params' => [
        'machine_token' => $token,
        'client'        => 'terminus',
      ],
      'method' => 'post',
    ];

    try {
      $response = $this->request->request(
        'authorize/machine-token',
        $options
      );
    } catch (\Exception $e) {
      throw new TerminusException(
        'The provided machine token is not valid.',
        [],
        1
      );
    }

    $data = $response['data'];
    $this->setInstanceData($response['data']);
    $user = Session::getUser();
    $user->fetch();
    $user_data = $user->serialize();
    if (isset($args['token'])) {
      $this->tokens_cache->add(
        ['email' => $user_data['email'], 'token' => $token,]
      );
    }
    return true;
  }

  /**
   * Execute the login via email/password
   *
   * @param string $email    Email address associated with a Pantheon account
   * @param string $password Password for the account
   * @return bool True if login succeeded
   * @throws TerminusException
   */
  public function logInViaUsernameAndPassword($email, $password) {
    if (!$this->isValidEmail($email)) {
      throw new TerminusException(
        $email . ' {email} is not a valid email address.',
        compact('email'),
        1
      );
    }

    $options = [
      'form_params' => [
        'email'    => $email,
        'password' => $password,
      ],
      'method' => 'post'
    ];
    try {
      $response = $this->request->request('authorize', $options);
      if ($response['status_code'] != '200') {
        throw new TerminusException();
      }
    } catch (TerminusException $e) {
      throw new TerminusException(
        'Login unsuccessful for {email}', compact('email'), 1
      );
    }

    $this->setInstanceData($response['data']);
    return true;
  }

  /**
   * Checks to see whether the email has been set with a machine token
   *
   * @param string $email Email address to check for
   * @return bool
   */
  public function tokenExistsForEmail($email) {
    $file_exists = $this->tokens_cache->tokenExistsForEmail($email);
    return $file_exists;
  }

  /**
   * Checks whether email is in a valid or not
   *
   * @param string $email String to be evaluated for email address format
   * @return bool True if $email is in email address format
   */
  private function isValidEmail($email) {
    $is_email = !is_bool(filter_var($email, FILTER_VALIDATE_EMAIL));
    return $is_email;
  }

  /**
   * Saves the session data to a cookie
   *
   * @param \stdClass $data Session data to save
   * @return bool Always true
   */
  private function setInstanceData(\stdClass $data) {
    if (!isset($data->machine_token)) {
      $machine_token = (array)Session::instance()->get('machine_token');
    } else {
      $machine_token = $data->machine_token;
    }
    $session = [
      'user_uuid'           => $data->user_id,
      'session'             => $data->session,
      'session_expire_time' => $data->expires_at,
    ];
    if ($machine_token && is_string($machine_token)) {
      $session['machine_token'] = $machine_token;
    }
    Session::instance()->setData($session);
    return true;
  }

}
