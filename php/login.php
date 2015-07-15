<?php

namespace Terminus\Login;

use DOMDocument;

/**
 * Start a dashboard session
 *
 * It doesn't follow the normal pattern since it's working off Drupal's login
 * forms directly. This will be refactored when there's a direct CLI auth
 * mechanism in the API itself.
 *
 * Many thanks to Amitai and the gang at: https://drupal.org/node/89710
 *
 * @param [string] $email    Pantheon account email
 * @param [string] $password Pantheon account password
 * @return [array] $data Session data
 */
function auth($email, $password) {
  if (!$email) {
    $email = drush_get_option('email');
  }

  $host = TERMINUS_HOST;
  $url  = "https://$host/login";

  $ch = curl_init();
  if (strpos(TERMINUS_HOST, 'onebox') !== false) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  }

  // Set URL and other appropriate options.
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // Grab URL and pass it to the browser.
  $result = curl_exec($ch);

  if (curl_errno($ch) != 0) {
    $err = curl_error($ch);
    curl_close($ch);
    $error = \Terminus::error("Dashboard unavailable: $err");
  }

  $form_build_id = get_form_build_id($result);

  // Attempt to log in.
  $login_data = array(
    'email' => $email,
    'password' => $password,
    'form_build_id' => $form_build_id,
    'form_id' => 'atlas_login_form',
    'op' => 'Login',
  );
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $result = curl_exec($ch);

  if (curl_errno($ch) != 0) {
    $curl_error = curl_error($ch);
    $error      = \Terminus::error("Dashboard unavailable: $curl_error");
  }

  curl_close($ch); // Close cURL resource and free up system resources

  $set_cookie_header = parse_drupal_headers($result, 'Set-Cookie');
  if(!$set_cookie_header) {
    $error_message = 'Authentication failed.
      Please check your credentials and try again.';
  }

  $session = get_session_from_header($set_cookie_header);
  if(!$session) {
    $error_message = 'Session not found.
      Please check your credentials and try again.';
  }

  // Get the UUID.
  $user_uuid = get_user_uuid_from_headers($result);
  if(!\Terminus\Utils\is_valid_uuid($user_uuid)) {
    $error_message = 'Could not determine user UUID.
      Please check your credentials and try again.';
  }

  if(isset($error_message)) {
    $error = \Terminus::error($error_message);
  }

  if(isset($error)) {
    return $error;
  }

  // Prepare credentials for storage.
  $data = array(
    'user_uuid' => $user_uuid,
    'session' => $session,
    'session_expire_time' => 
      get_session_expiration_from_header($set_cookie_header),
    'email' => $email,
  );

  return $data;
}

/**
 * Parse form build ID
 *
 * @param [string] $html Login form HTML to parse for ID
 * @return [string] $build_id The login form build ID
 */
function get_form_build_id($html) {
  if(!$html) {
    return false;
  }
  // Parse form build ID.
  $DOM = new DOMDocument;
  $DOM->loadHTML($html);
  $login_form = $DOM->getElementById('atlas-login-form');
  if(!$login_form) {
    $error = \Terminus::error(
      "Dashboard unavailable: login endpoint not found."
    );
    return $error;
  }

  foreach($login_form->getElementsByTagName('input') as $input) {
    if($input->getAttribute('name') == 'form_build_id') {
      $build_id = $input->getAttribute('value');
      return $build_id;
    }
  }
  return false;
}

/**
 * Parse session expiration out of a header
 *
 * @param [string] $session_header The session header
 * @return [int] Unix timestamp of session expiration time
 */
function get_session_expiration_from_header($session_header) {
  $session_info = explode('; ', $session_header);
  foreach($session_info as $pair) {
    if(strpos($pair, 'expires') === 0) {
      $expiration_array = explode('=', $pair);
      $expiration       = strtotime($expiration_array[1]);
      return $expiration;
    }
  }
}

/**
 * Parse session out of a header
 * 
 * @param [string] $header Contains request header
 * @return [string] $session Session ID
 */
function get_session_from_header($header) {
  $session    = false;
  $set_cookie = explode('; ', $header);
  foreach($set_cookie as $cookie) {
    if(strpos($cookie, 'SSESS') === 0) {
      $session = $cookie;
    }
  }
  return $session;
}

/**
 * Parse user ID out of headers
 *
 * @param [string] $headers Contains request headers
 * @return [string] $uuid Atlas UUID
 */
function get_user_uuid_from_headers($headers) {
  $location_header = parse_drupal_headers($headers, 'Location');
  if(!$location_header) {
    return false;
  }
  // https://terminus.getpantheon.com/users/UUID
  $parts = explode('/', $location_header);
  $uuid  = array_pop($parts);
  return $uuid;
}

/**
 * Helper function for parsing Drupal headers for login
 *
 * @param [string] $result        String containing headers
 * @param [string] $target_header Key of header to return
 * @return [string] $headers[$target_header]
 */
function parse_drupal_headers($result, $target_header = 'Set-Cookie') {
  //Check that we have a 302 and a session.
  list($headers_text, $html) = explode("\r\n\r\n", $result, 2);
  if(strpos($headers_text, "100 Continue") !== false) {
    list($headers_text, $html) = explode("\r\n\r\n", $html, 2);
  }
  $header_lines = explode("\r\n", $headers_text);
  $status       = array_shift($header_lines);
  if(strpos($status, "302 Moved Temporarily") === false) {
    return false;
  }
  $headers = array();
  foreach($header_lines as $line) {
    $parts = explode(': ', $line);
    if(isset($parts[1])) {
      $headers[$parts[0]] = $parts[1];
    }
  }
  if(isset($headers[$target_header])) {
    return $headers[$target_header];
  }

  return false;
}
