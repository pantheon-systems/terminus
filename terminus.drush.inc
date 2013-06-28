<?php
 /**
 * @file
 *  Terminus: programatic access to the Pantheon Platform via Drush.
 */

/**
 * Some constants we use.
 */
define('ATLAS_HOST', 'dashboard.getpantheon.com');
define('ATLAS_PORT', 443);

/**
 * Implementation of hook_drush_command().
 *
 * List of available terminus commands.
 *
 * @return
 *   An associative array describing your command(s).
 */
function terminus_drush_command() {
  $items = array();

  // The 'pantheon-authenticate' command
  $items['pantheon-auth'] = array(
    'description' => "Authenticate against the pantheon dashboard. Required before doing anything else.",
    'arguments' => array(
      'email' => 'Email address of dashboard account',
    ),
    'options' => array(
      'password' => array(
        'description' => 'Optional: include password for script-friendly use.',
        'example-value' => 'mypassword',
      ),
      'nocache' => array(
        'description' => 'Force a refresh of cached authentication session.',
      ),
    ),
    'examples' => array(
      'drush pauth josh@getpantheon.com --password=mypassword' => 'Get authentication token.',
    ),
    'aliases' => array('pauth'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-logout'] = array(
    'description' => "Clear any stored session data.",
    'examples' => array(
      'drush plogout',
    ),
    'aliases' => array('plogout'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  // The 'pantheon-sitelist' command
  $items['pantheon-sites'] = array(
    'description' => "List your sites.",
    'options' => array(
      'nocache' => array(
        'description' => 'Force a refresh of cached authentication session.',
      ),
      'json' => array(
        'description' => 'Return output in JSON. Useful for scripting.',
      ),
    ),
    'examples' => array(
      'drush psites --nocache' => 'Get a fresh list of sites.',
    ),
    'aliases' => array('psites'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  return $items;
}

/**
 * This is a curl-based login tool to start a session in the dashboard.
 *
 * Many thanks to Amitai and the gang at: https://drupal.org/node/89710
 *
 */
function drush_terminus_pantheon_auth($email) {
    $nocache = drush_get_option('nocache');
    $session_data = terminus_session_data();
    if (!$nocache && $session_data) {
      drush_log(dt('Cached auth found for !email', array('!email' => $email)), 'notice');
      if (terminus_validate_session($session_data)) {
        drush_log('Session still valid. Good to go!', 'ok');
        return TRUE;
      }
    }
    $password = drush_get_option('password');
    if (!$password) {
      // Hide password input in the clear.
      // TODO: consider an alternative ways to start a session using a shared
      // secret key. This would allow us to differentiate between dashboard
      // users and drush users.
      drush_print("Pantheon dashboard password: ");
      system('stty -echo');
      $password = trim(fgets(STDIN));
      system('stty echo');
      // $password = drush_prompt(dt('Pantheon dashboard password'), NULL, TRUE, TRUE);
    }
    drush_log(dt('Authenticating as !email', array('!email' => $email)), 'notice');
    $ch = curl_init();
    $url = 'https://'. ATLAS_HOST .'/login';
    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // grab URL and pass it to the browser
    $result = curl_exec($ch);

    if (curl_errno($ch) != 0) {
      $err = curl_error($ch);
      return drush_set_error("Dashboard unavailable", "Dashboard unavailable: $err");
    }

    // Parse form build ID.
    $DOM = new DOMDocument;
    @$DOM->loadHTML($result);
    $login_form = $DOM->getElementById('atlas-login-form');
    if (!$login_form) {
      return drush_set_error("Dashboard unavailable", "Dashboard unavailable: login endpoint not found.");
    }

    foreach ($login_form->getElementsByTagName('input') as $input) {
      if ($input->getAttribute('name') == 'form_build_id') {
        $form_build_id = $input->getAttribute('value');
        break;
      }
    }

    // Now login.
    $login_data = array('email' => $email,
                        'password' => $password,
                        'form_build_id' => $form_build_id,
                        'form_id' => 'atlas_login_form',
                        'op' => 'Login');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $result = curl_exec($ch);

    if (curl_errno($ch) != 0) {
      $err = curl_error($ch);
      return drush_set_error("Dashboard unavailable", "Dashboard unavailable: $err");
    }

    $set_cookie_header = terminus_parse_drupal_headers($result, 'Set-Cookie');
    if (!$set_cookie_header) {
      return drush_set_error("Authentication Failure!", 'Authentication failed. Please check your credentials and try again.');
    }
    $session = FALSE;
    $set_cookie = explode('; ', $set_cookie_header);
    foreach ($set_cookie as $cookie) {
      if (strpos($cookie, 'SSESS') === 0) {
        $session = $cookie;
      }
    }
    if (!$session) {
      return drush_set_error("Authentication Failure!", 'Session not found. Please check your credentials and try again.');
    }

    // Grab the UUID Now
    curl_setopt($ch, CURLOPT_COOKIE, $session);
    curl_setopt($ch, CURLOPT_URL, 'https://'. ATLAS_HOST .'/account');
    $result = curl_exec($ch);
    $location_header = terminus_parse_drupal_headers($result, 'Location');
    if (!$location_header) {
      return drush_set_error("Authentication Failure!", 'Could not determine user UUID. Please check your credentials and try again.');
    }
    $user_uuid = array_pop(explode('/', $location_header));

    drush_log('Authentication success!', 'ok');
    drush_log(dt('User UUID: !user_uuid', array('!user_uuid' => $user_uuid)), 'notice');
    $data = array('user_uuid' => $user_uuid, 'session' => $session, 'email' => $email);
    drush_cache_set('terminus-current-session', $data, 'pantheon');

    // Store the session in drush cache.
    // close cURL resource, and free up system resources
    curl_close($ch);
}

/**
 * Get rid of the current session.
 */
function drush_terminus_pantheon_logout() {
  drush_cache_clear_all('*', 'pantheon', TRUE);
  drush_log('Removed session token and cleared all locally cached data.', 'ok');
}

/**
 * Get a list of sites.
 */
function drush_terminus_pantheon_sites() {
  $user_uuid = terminus_session_data('user_uuid');
  $nocache = drush_get_option('nocache');
  $json_output = drush_get_option('json');
  if (!$user_uuid) {
    return drush_set_error("No user UUID found. Are you authenticated?");
  }
  if ($nocache || !$cache = drush_cache_get('terminus-sites', 'pantheon')) {
    drush_log('Loading site data from Pantheon.', 'notice');
    $result = terminus_pseudoproxy_request('user', $user_uuid, 'sites');
    $sites = $result['json'];
    drush_cache_set('terminus-sites', $sites, 'pantheon');
  }
  else {
    drush_log('Loaded site data from cache.', 'notice');
    $sites = $cache->data;
  }
  if ($json_output) {
    drush_print($sites);
    return TRUE;
  }

  // Display tabular output.
  $sites = json_decode($sites);
  $rows = array();
  foreach ($sites as $site_uuid => $data) {
    $i = $data->information;
    $rows[$i->name] = array($i->name, $i->service_level, $site_uuid);
  }
  ksort($rows);
  array_unshift($rows, array("Site", "Service Level", "UUID"));
  drush_print_table($rows, TRUE);
}

/**
 * API Access
 */


/**
 * Get the current session data.
 */
function terminus_session_data($key = FALSE) {
  static $cache = FALSE;
  if (!$cache) {
    $cache = drush_cache_get('terminus-current-session', 'pantheon');
  }
  if (!$cache) {
    // No session data. That's not good.
    return FALSE;
  }
  if ($key && isset($cache->data[$key])) {
    return $cache->data[$key];
  }
  elseif($key) {
    return FALSE;
  }
  return $cache->data;
}

/**
 * Validate cached session data.
 */
function terminus_validate_session($data) {
  $result = terminus_pseudoproxy_request('user', $data['user_uuid'], 'email');
  $email = @json_decode($result['json']);
  return $email == $data['email'];
}

/**
 * Helper function for parsing Drupal headers.
 */
function terminus_parse_drupal_headers($result, $target_header='Set-Cookie') {
  // Check that we have a 302 and a session.
  list ($headers_text, $html) = explode("\r\n\r\n", $result, 2);
  if (strpos($headers_text, "100 Continue") !== false) {
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

function terminus_pseudoproxy_request($realm, $uuid, $path = FALSE, $method = 'GET', $data = NULL) {
  static $ch = FALSE;
  if (!$ch) {
    $ch = curl_init();
  }
  $session  = terminus_session_data('session');
  $url = 'https://'. ATLAS_HOST .'/pp.php?source=terminus'. $realm .'='. $uuid;
  if ($path) {
    $url .= '&path='. urlencode($path);
  }
  if ($data) {
    // $data for POSTs, PUTs, DELETEs are sent as JSON.
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
      $data = json_encode($data);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      array_push($headers, 'Content-Type: application/json', 'Content-Length: '. strlen($data));
    }
    // $data for GETs is sent as querystrings.
    else if ($method === "GET") {
      $url .= "?" . http_build_query($data);
    }
  }
  // set URL and other appropriate options
  $opts = array(
    CURLOPT_URL => $url,
    CURLOPT_HEADER => 1,
    CURLOPT_PORT => ATLAS_PORT,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_COOKIE => $session,
  );
  curl_setopt_array($ch, $opts);

  // grab URL and pass it to the browser
  $result = curl_exec($ch);
  list($headers_text, $json) = explode("\r\n\r\n", $result, 2);
  // Work around extra 100 Continue headers - http://stackoverflow.com/a/2964710/1895669
  if(strpos($headers_text," 100 Continue")!==false){
    list($headers_text, $json) = explode( "\r\n\r\n", $json , 2);
  }

  if (curl_errno($ch) != 0) {
    $error = curl_error($ch);
    curl_close($ch);
    echo "CONNECTION ERROR: $error\n";
    exit(1);
  }

  return array('headers' => $headers_text, 'json' => $json);
}

function _terminus_service_level_name($level) {
  $levels = array(
    'legacy_basic' => 'personal',
    'basic' => 'personal',
    'legacy_pro' => 'pro',
  );
  if (isset($levels[$level])) {
    return $levels[$level];
  }
  return $level;
}
