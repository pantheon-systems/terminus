<?php

namespace WP_CLI\Login;
use DOMDocument;

/**
 * Parse form build ID.
 *
 * @param $html
 * @return string
 */
function get_form_build_id($html) {
  if (!$html) {
    return FALSE;
  }
  // Parse form build ID.
  $DOM = new DOMDocument;
  @$DOM->loadHTML($html);
  $login_form = $DOM->getElementById('atlas-login-form');
  if (!$login_form) {
    return \WP_CLI::error("Dashboard unavailable", "Dashboard unavailable: login endpoint not found.");
  }

  foreach ($login_form->getElementsByTagName('input') as $input) {
    if ($input->getAttribute('name') == 'form_build_id') {
      return $input->getAttribute('value');
    }
  }
  return FALSE;
}

/**
 * Parse session expiration out of a header.
 * @param $session_header
 * @return int
 */
function get_session_expiration_from_header($session_header) {
  $session_info = explode('; ', $session_header);
  foreach ($session_info as $pair) {
    if (strpos($pair, 'expires') === 0) {
      $expiration = explode('=', $pair);
      return strtotime($expiration[1]);
    }
  }
}

/**
 * Parse session out of a header.
 * @param $header
 * @return string
 */
function get_session_from_header($header) {
  $session = FALSE;
  $set_cookie = explode('; ', $header);
  foreach ($set_cookie as $cookie) {
    if (strpos($cookie, 'SSESS') === 0) {
      $session = $cookie;
    }
  }
  return $session;
}

/**
 * Parse user ID out of headers.
 * @param $headers
 * @return string
 */
function get_user_uuid_from_headers($headers) {
  $location_header = parse_drupal_headers($headers, 'Location');
  if (!$location_header) {
    return FALSE;
  }
  // https://terminus.getpantheon.com/users/UUID
  $parts = explode('/', $location_header);
  return array_pop($parts);
}

/**
 * Start a dashboard session.
 *
 * It doesn't follow the normal pattern since it's working off Drupal's login
 * forms directly. This will be refactored when there's a direct CLI auth
 * mechanism in the API itself.
 *
 * Many thanks to Amitai and the gang at: https://drupal.org/node/89710
 */
function auth($email, $password) {
  if (!$email) {
    $email = drush_get_option('email');
  }
  $ch = curl_init();
  $host = TERMINUS_HOST;
  if (strpos(TERMINUS_HOST, 'onebox') !== FALSE) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  }
  $url = 'https://'. $host .'/login';

  // Set URL and other appropriate options.
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // Grab URL and pass it to the browser.
  $result = curl_exec($ch);

  if (curl_errno($ch) != 0) {
    $err = curl_error($ch);
    curl_close($ch);
    return \WP_CLI::error("Dashboard unavailable", "Dashboard unavailable: $err");
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
    $err = curl_error($ch);
    return \WP_CLI::error("Dashboard unavailable", "Dashboard unavailable: $err");
  }

  // Close cURL resource, and free up system resources.
  curl_close($ch);

  $set_cookie_header = parse_drupal_headers($result, 'Set-Cookie');
  if (!$set_cookie_header) {
    return \WP_CLI::error("Failure!", 'Authentication failed. Please check your credentials and try again.');
  }

  $session = get_session_from_header($set_cookie_header);

  if (!$session) {
    return \WP_CLI::error("Failure!", 'Session not found. Please check your credentials and try again.');
  }

  // Get the UUID.
  $user_uuid = get_user_uuid_from_headers($result);
  if (!\WP_CLI\Utils\is_valid_uuid($user_uuid)) {
    return \WP_CLI::error("Failure!", 'Could not determine user UUID. Please check your credentials and try again.');
  }

  // Prepare credentials for storage.
  $data = array(
    'user_uuid' => $user_uuid,
    'session' => $session,
    'session_expire_time' => get_session_expiration_from_header($set_cookie_header),
    'email' => $email,
  );

  return $data;
}

/**
 * Helper function for parsing Drupal headers for login.
 */
function parse_drupal_headers($result, $target_header='Set-Cookie') {
  // Check that we have a 302 and a session.
  list ($headers_text, $html) = explode("\r\n\r\n", $result, 2);
  if (strpos($headers_text, "100 Continue") !== FALSE) {
    list ($headers_text, $html) = explode("\r\n\r\n", $html , 2);
  }
  $header_lines = explode("\r\n", $headers_text);
  $status = array_shift($header_lines);
  if (strpos($status, "302 Moved Temporarily") === FALSE) {
    return FALSE;
  }
  $headers = array();
  foreach ($header_lines as $line) {
    $parts = explode(': ', $line);
    if (isset($parts[1])) {
      $headers[$parts[0]] = $parts[1];
    }
  }
  if (isset($headers[$target_header])) {
    return $headers[$target_header];
  }

  return FALSE;
}
