<?php
 /**
 * @file
 *  Terminus: programatic access to the Pantheon Platform via Drush.
 */

/**
 * Some constants we use.
 */
define('TERMINUS_HOST', 'terminus.getpantheon.com');
define('TERMINUS_PORT', 443);
date_default_timezone_set('UTC');


/**
 * An easy function to override constants to allow development internally.
 *
 * Also bootstraps the included library functions, which are separate so people
 * can use them outside the context of Drush.
 *
 * TODO: use for other settings.
 */
function terminus_bootstrap($validate = TRUE) {
  include_once('terminus.backup.api.inc');
  include_once('terminus.site.api.inc');
  include_once('terminus.user.api.inc');
  $session_data = terminus_session_data();
  // DRY session validation.
  if ($validate) {
    if (!isset($session_data['user_uuid'])) {
      return drush_set_error("No user UUID found. Please authenticate first using 'drush pauth'");
    }
  }
  // Common command flags.
  $session_data['nocache'] = drush_get_option('nocache');
  $session_data['json'] = drush_get_option('json');
  if (isset($session_data['onebox']) || drush_get_option('onebox')) {
    $session_data['onebox'] = TRUE;
  }

  return $session_data;
}

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

  $items['pantheon-logout'] = array(
    'description' => "Clear any stored session data.",
    'examples' => array(
      'drush plogout',
    ),
    'aliases' => array('plogout'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

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
    ),
    'examples' => array(
      'drush pauth josh@getpantheon.com --password=mypassword' => 'Get authentication token.',
    ),
    'aliases' => array('pauth'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );


  // The 'pantheon-sitelist' command
  $items['pantheon-sites'] = array(
    'description' => "List your sites.",
    'examples' => array(
      'drush psites --nocache' => 'Get a fresh list of sites.',
    ),
    'aliases' => array('psites'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-hostname-add'] = array(
    'description' => "Add a hostname to a site you control.",
    'arguments' => array(
      'site' => 'The site UUID you are working on.',
      'environment' => 'The environemnt (e.g. "live").',
      'hostname' => 'The hostname (e.g. "www.mysite.com")',
    ),
    'aliases' => array('psite-hostname-add'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-hostname-remove'] = array(
    'description' => "Remove a hostname from a site you control.",
    'arguments' => array(
      'site' => 'The site UUID you are working on.',
      'environment' => 'The environemnt (e.g. "live").',
      'hostname' => 'The hostname (e.g. "www.mysite.com")',
    ),
    'aliases' => array('psite-hostname-remove'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-products'] = array(
    'description' => "Get the list of available Drupal product start-states.",
    'aliases' => array('pproducts'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-create'] = array(
    'description' => "Create a new site on Pantheon",
    'arguments' => array(
      'name' => 'Shortname of the site to create. Will be part of the url.',
    ),
    'options' => array(
      'label' => array(
        'description' => 'Human-friendly site label.',
      ),
      'product' => array(
        'description' => 'UUID of the product you want to start with (see pantheon-products).',
      ),
      'organization' => array(
        'description' => 'UUID of an organization you want the site to be a part of.',
      ),
    ),
    'aliases' => array('psite-create'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-delete'] = array(
    'description' => "Delete a site from Pantheon.",
    'arguments' => array(
      'site' => 'UUID of the site you want to delete.',
    ),
    'aliases' => array('psite-delete'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-backups'] = array(
    'description' => "List site backups (and exports).",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
    ),
    'aliases' => array('psite-backups'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-get-backup'] = array(
    'description' => "Get a download link to a specific site backup.",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
      'bucket' => 'Bucket for the backup.',
      'element' => 'Which part of the backup do you want?',
    ),
    'aliases' => array('psite-get-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-make-backup'] = array(
    'description' => "Trigger an on-demand backup for a site/environment.",
    'arguments' => array(
      'site' => 'UUID of the site you want to make a backup for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
    ),
    'aliases' => array('psite-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-download-backup'] = array(
    'description' => "Download a backup file from a specific site.",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to download a back up from.',
      'bucket' => 'Bucket for the backup. Use "latest" for the most recent.',
      'element' => 'Which part of the backup do you want? (e.g. database, files, code)',
      'destination' => 'Where would you like the backup?'
    ),
    'aliases' => array('psite-dl-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-load-backup'] = array(
    'description' => "Load db with a backup file from a specific site.",
    'arguments' => array(
      'site' => 'UUID or name of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to use a backup from.',
      'bucket' => 'Bucket for the backup. Use "latest" for the most recent.',
    ),
    'aliases' => array('psite-load-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-site-connection-mode'] = array(
    'description' => "Set or retrieve the connection mode of a specific site/environment.",
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'The environment (e.g. "live") you would like to target.',
      'mode' => 'Connection mode like to set. (e.g. "sftp" or "git") Leave blank to retrieve the current mode.',
    ),
    'aliases' => array('psite-cmode'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  /**
   * Utility Functions.
   */
  $items['pantheon-aliases'] = array(
    'description' => "Update the Pantheon Drush alias file at ~/.drush/pantheon.aliases.drushrc.php.",
    'options' => array(
      'destination' => array(
        'description' => 'Specify the destination to save the alias file.',
      ),
    ),
    'aliases' => array('paliases'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  $items['pantheon-pp'] = array(
    'description' => "Direct pseudoproxy interface. JSON only, for debugging.",
    'arguments' => array(
      'realm' => 'Are you asking about users or sites?',
      'uuid' => 'The unique id of the thing you want to know about.',
      'path' => 'Optional: path extension for more specific commands.',
    ),
    'aliases' => array('pp'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap at all.
  );

  // Include standard options for all commands:
  $common = array(
    'nocache' => array(
      'description' => 'Force a refresh of cached authentication session.',
    ),
    'json' => array('description' => 'Return raw JSON if possible.'),
    'onebox' => array('description' => 'Use onebox (Pantheon dev only).'),
  );

  foreach ($items as $key => $array) {
    if (isset($array['options'])) {
      $items[$key]['options'] = array_merge($items[$key]['options'], $common);
    }
    else {
      $items[$key]['options'] = $common;
    }
  }

  return $items;
}


/**
 * @group Drush Commands
 *
 * These commands exercise various aspects of the API.
 */

/**
 * User data about self.
 */
function drush_terminus_pantheon_whoami() {
  $session_data = terminus_session_data();
  // import to local scope variables
  extract($session_data);
  drush_print(dt("User UUID: !uuid", array('!uuid' => $user_uuid)));
  drush_print(dt("Email: !email", array('!email' => $email)));
  return TRUE;
}


/**
 * Get a list of sites.
 */
function drush_terminus_pantheon_sites() {
  $session_data = terminus_bootstrap();

  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  $cid = 'terminus-sites';
  if ($nocache || !$cache = drush_cache_get($cid, 'pantheon')) {
    drush_log('Loading site data from Pantheon.', 'notice');
    $result = terminus_api_user_site_list($user_uuid);
    if ($result === FALSE) {
      return FALSE;
    }
    $sites = $result['json'];
    drush_cache_set($cid, $sites, 'pantheon');
  }
  else {
    drush_log('Loaded site data from cache.', 'notice');
    $sites = $cache->data;
  }
  if ($json) {
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
 * Create a new site via the CLI.
 */
function drush_terminus_pantheon_site_create($name = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$name) {
    drush_log('You must supply a site name', 'failed');
    return FALSE;
  }

  // Construct payload. We don't want empty/null values.
  $site_data = array();
  $site_data['name'] = preg_replace("/[^A-Za-z0-9 ]/", '', $name);
  if (drush_get_option('label')) {
    $site_data['label'] = drush_get_option('label');
  }
  if (drush_get_option('organization')) {
    $site_data['organization'] = drush_get_option('organization');
  }
  if (drush_get_option('product')) {
    $site_data['product'] = drush_get_option('product');
  }

  $result = terminus_request('user', $user_uuid, 'sites', 'POST', $site_data);
  if (!$result) {
    drush_log("Site create failed", "failed");
    return FALSE;
  }
  if (drush_get_option('json')) {
    drush_print($result['json']);
    return TRUE;
  }
  drush_log("Site is building now in your dashboard.", "ok");
  drush_print("The new site's UUID is '". json_decode($result['json']) ."'");
  drush_cache_clear_all('terminus-sites', 'pantheon');
  return TRUE;
}

/**
 * Delete a site via the CLI.
 */
function drush_terminus_pantheon_site_delete($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  $confirm = drush_confirm("Are you sure you want to delete?");
  if (!$confirm) {
    return TRUE;
  }

  $result = terminus_api_site_delete($site_uuid);
  drush_log("Site deleted.", "ok");
  drush_cache_clear_all('terminus-sites', 'pantheon');
  return TRUE;
}


/**
 * Add a hostname.
 */
function drush_terminus_pantheon_hostname_add($site_uuid = FALSE, $environment = FALSE, $hostname = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  $hostname = drush_prompt(dt('Hostname (e.g. "www.mysite.com") you would like to add'), NULL, TRUE);
  if (!preg_match('/(?=^.{1,254}$)(^(?:(?!\d|-)[a-z0-9\-]{1,63}(?<!-)\.)+(?:[a-z]{2,})$)/i', $hostname)) {
    drush_log("'$hostname' is not a valid hostname.", 'failed');
    return FALSE;
  }
  $result = terminus_api_site_environment_add_hostname($site_uuid, $environment, $hostname);
  if ($result === FALSE) {
    return FALSE;
  }
  drush_log("Added '$hostname' to the $environment environment", 'ok');
  return TRUE;
}

/**
 * Remove a hostname.
 */
function drush_terminus_pantheon_hostname_remove($site_uuid = FALSE, $environment = FALSE, $hostname = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid) {
    $site_uuid = terminus_session_select_data('site_uuid');
    if (!$site_uuid) {
      drush_log('You must supply a site UUID', 'failed');
      return FALSE;
    }
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  if (!$hostname) {
    $hostname = terminus_session_select_data('hostname', $site_uuid, $environment);
    if (!$hostname) {
      drush_log('You must choose a hostname', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_site_environment_delete_hostname($site_uuid, $environment, $hostname);
  if ($result === FALSE) {
    return FALSE;
  }
  drush_log("Removed '$hostname' from the $environment environment", 'ok');
  return TRUE;
}

/**
 * Get a list of backups for a site.
 */
function drush_terminus_pantheon_site_backups($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_backup_catalog($site_uuid, $environment);
  $rows = array();
  $backups = json_decode($result['json']);
  foreach ($backups as $id => $a) {
    if (!isset($a->filename)) {
      continue;
    }
    $parts = explode('_', $id);
    $type = strpos($id, 'backup') ? 'backup' : 'export';
    $size = round($a->size/(1024*1024), 2) .' MB';
    $rows[$a->filename] = array($type, date(DATE_RFC822, $a->timestamp), $parts[2], $parts[0] .'_'. $parts[1], $size);
  }
  krsort($rows);
  if (drush_get_option('json')) {
    drush_print(json_encode($rows));
    return TRUE;
  }
  array_unshift($rows, array("Type", "Date", "Element", "Bucket", "Size"));
  drush_print_table($rows, TRUE);
}

/**
 * Get a list of backups for a site.
 */
function drush_terminus_pantheon_site_get_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE, $element = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element);
  if (drush_get_option('json')) {
    drush_print($result['json']);
    return TRUE;
  }
  $data = json_decode($result['json']);
  drush_print(dt('Signed url for the backup:'));
  drush_print($data->url);
  return TRUE;
}

/**
 * Make a backup. Uses more generic backup api.
 */
function drush_terminus_pantheon_site_make_backup($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if (!$session_data) {
    drush_log('You are not authenticated', 'failed');
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  $result = terminus_api_site_make_backup($site_uuid, $environment, 'backup');
  drush_print("Backup initiated.");
  return TRUE;
}

/**
 * Download a backup from a site.
 */
function drush_terminus_pantheon_site_download_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE, $element = FALSE, $destination = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  if (!$element) {
    $element = terminus_session_select_data('element');
    if (!$element) {
      drush_log('You must supply an element', 'failed');
      return FALSE;
    }
  }

  // Retrieve the latest bucket
  if ($bucket == 'latest') {
    $bucket = terminus_latest_bucket($site_uuid, $environment, $element);
  }
  if (!$bucket) {
    $bucket = terminus_session_select_data('bucket', $site_uuid, $environment, $element);
    if (!$bucket) {
      drush_log('You must supply a bucket', 'failed');
      return FALSE;
    }
  }
  if (!$destination) {
    $destination = terminus_session_select_data('destination');
    if (!$destination) {
      drush_log('You must supply a destination', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element);
  $data = json_decode($result['json']);
  $filename = strstr(basename($data->url), '?', '_');

  drush_log("Downloading " . $filename . "...");

  // Copied from pm code
  $cache_duration = 86400*365;
  $path = _drush_download_file($data->url, $destination . DIRECTORY_SEPARATOR . $filename, $cache_duration);
  if ($path || drush_get_context('DRUSH_SIMULATE')) {
    drush_log("Downloading " . $filename . " was successful.");
  }
  else {
    return drush_set_error('DRUSH_PSITE_DOWNLOAD_FAILED', 'Unable to download ' . $filename . ' to ' . $destination . ' from '. $data->url);
  }

  return TRUE;
}

/**
 * Load a database with a backup from a site.
 */
function drush_terminus_pantheon_site_load_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $element = 'database';

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  // Retrieve the latest bucket
  if ($bucket == 'latest' || (!$bucket && $site_uuid && $environment)) {
    $bucket = terminus_latest_bucket($site_uuid, $environment, $element);
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  if (!$bucket) {
    $bucket = terminus_session_select_data('bucket', $site_uuid, $environment, $element);
    if (!$bucket) {
      drush_log('You must supply a bucket', 'failed');
      return FALSE;
    }
  }

  if (!drush_confirm('Overwrite this database with ' . $environment . '.' . $site_uuid . '?')) {
    drush_log('Cancelled', 'failed');
    return FALSE;
  }

  $destination = drush_tempdir();
  $result = terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element);
  $data = json_decode($result['json']);
  $filename = strstr(basename($data->url), '?', '_');

  $cache_duration = 86400*365;
  $path = _drush_download_file($data->url, $destination . DIRECTORY_SEPARATOR . $filename, $cache_duration);
  if (!$path && !drush_get_context('DRUSH_SIMULATE')) {
    return drush_set_error('DRUSH_PSITE_DOWNLOAD_FAILED', 'Unable to download ' . $filename . ' to ' . $destination . ' from '. $data->url);
  }

  drush_log("Downloading " . $filename . " was successful.");

  // TODO: There's got to be a better way to handle a .gz file here
  shell_exec('gunzip ' . $path);
  $path = strstr($path, '.gz', TRUE);

  drush_set_option('file', $path);
  drush_sql_query();

  drush_log("Database load complete.");
  drush_delete_dir($path);

  return TRUE;
}

/**
 * Set or retrieve the connection mode for a site/environment.
 */
function drush_terminus_pantheon_site_connection_mode($site_uuid = FALSE, $environment = FALSE, $cmode = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  // Only prompt for $cmode if the command is run without a site or environment
  $prompt_cmode = !$site_uuid || !$environment;

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  if ($prompt_cmode && !$cmode) {
    $cmode = terminus_session_select_data('cmode', $site_uuid);
  }

  if (!$cmode) {
    $result = terminus_api_site_environment_onserverdev_get($site_uuid, $environment);
    $data = json_decode($result['json']);
    $mode = $data->enabled? 'SFTP' : 'Git';
    drush_log("Connection mode: " . $mode, 'ok');
    return TRUE;
  }

  $cmode = strtolower($cmode);
  $onserverdevstatus = $cmode == 'sftp' ? TRUE : FALSE;
  terminus_api_site_environment_onserverdev_set($site_uuid, $environment, $onserverdevstatus);

  $mode = $onserverdevstatus ? 'SFTP' : 'Git';
  drush_log('Connection mode set to: ' . $mode, 'ok');
}

/**
 * Update drush aliases file.
 */
function drush_terminus_pantheon_aliases() {
  $data = terminus_session_data();
  $default_destination = FALSE;
  $home = drush_server_home();
  if ($home) {
    $default_destination = drush_normalize_path($home . '/.drush/pantheon.aliases.drushrc.php');
  }
  $destination = drush_get_option('destination', $default_destination);
  if ($destination == FALSE) {
    drush_log('Could not auto-determine destination. Please provide one using the --destination option.', 'failed');
    return FALSE;
  }
  $fh = fopen($destination, 'w');
  if ($fh === FALSE) {
    drush_log('Cannot write aliases to "'. $destination .'" - verify this location is writable.', 'failed');
    return FALSE;
  }
  if (!$data) {
    drush_log('You are not authenticated', 'failed');
    return FALSE;
  }
  drush_log('Loading aliases from Pantheon.', 'notice');
  // This one is a little wonky still. Goes to Drupal.
  $ch = curl_init();
  $url = 'https://'. TERMINUS_HOST .'/users/'. $data['user_uuid'] .'/drushrc';
  curl_setopt($ch, CURLOPT_COOKIE, $data['session']);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if (TERMINUS_HOST == 'onebox.getpantheon.com') {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  }

  $result = curl_exec($ch);
  if ($result === FALSE) {
    drush_log('Failed fetching aliases. Try again?');
    return FALSE;
  }
  drush_log('Writing aliases to "'. $destination .'".', 'notice');
  fwrite($fh, $result);
  fclose($fh);
  drush_log('Pantheon aliases updated!.', 'ok');

  return TRUE;
}

/**
 * API Debugging command. Try out anything you like.
 */
function drush_terminus_pantheon_pp($realm, $uuid, $path=FALSE) {
  $result = terminus_request($realm, $uuid, $path);
  if ($result === FALSE) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Get the list of products.
 */
function drush_terminus_pantheon_products() {
  $nocache = drush_get_option('nocache');
  $json_output = drush_get_option('json');
  $cid = 'terminus-products';
  if ($nocache || !$cache = drush_cache_get($cid, 'pantheon')) {
    drush_log('Loading site data from Pantheon.', 'notice');
    $result = terminus_request('public', 'public', 'products');
    if ($result === FALSE) {
      return FALSE;
    }
    $products = $result['json'];
    drush_cache_set($cid, $products, 'pantheon');
  }
  else {
    drush_log('Loaded site data from cache.', 'notice');
    $products = $cache->data;
  }
  if ($json_output) {
    drush_print($products);
    return TRUE;
  }

  // Display tabular output.
  $products = json_decode($products);
  $rows = array();
  foreach ($products as $product_uuid => $data) {
    $i = $data->attributes;
    if ($i->organization_uuid == '') {
      $rows[$i->machinename] = array($i->longname, $i->author, $product_uuid);
    }
  }
  ksort($rows);
  array_unshift($rows, array("Product", "Maker", "UUID"));
  drush_print_table($rows, TRUE);
}

/**
 * Get rid of the current session.
 */
function drush_terminus_pantheon_logout() {
  drush_cache_clear_all('*', 'pantheon', TRUE);
  drush_log('Removed session token and cleared all locally cached data.', 'ok');
}

/**
 * Get or set current session data from cache.
 */
function terminus_session_data($key = FALSE) {
  static $cache = FALSE;
  if (!$cache) {
    $cache = drush_cache_get('terminus-current-session', 'pantheon');
    if (time() - $cache->created > 3600) {
      // Only allow cached auth for an hour.
      drush_cache_clear_all('*', 'pantheon', TRUE);
      return FALSE;
    }
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
  $result = terminus_request('user', $data['user_uuid'], 'email');
  if ($result === FALSE) {
    return FALSE;
  }
  $email = @json_decode($result['json']);
  return $email == $data['email'];
}

/**
 * Make a request to the Dashbord's internal API.
 *
 * @param $realm
 *    Permissions realm for data request: currently "user" or "site" but in the
 *    future this could also be "orgnaization" or another high-level business
 *    object (e.g. "product" for managing your app). Can also be "public" to
 *    simply pull read-only data that is not privileged.
 *
 * @param $uuid
 *    The uuid of the item in the realm you want to access.
 *
 * @param $method
 *    HTTP method (verb) to use.
 *
 * @param $data
 *    A native php data structure (int, string, arary or simple object) to be
 *    sent along with the request. Will be encoded as JSON for you.
 */
function terminus_request($realm, $uuid, $path = FALSE, $method = 'GET', $data = NULL) {
  static $ch = FALSE;
  if (!$ch) {
    $ch = curl_init();
  }
  $headers = array();
  $session_data = terminus_bootstrap(FALSE);
  $host = TERMINUS_HOST;
  if ($session_data['onebox']) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $host = 'onebox.getpantheon.com';
  }
  $url = 'https://'. $host .'/terminus.php?'. $realm .'='. $uuid;
  if ($path) {
    $url .= '&path='. urlencode($path);
  }
  if ($data) {
    // $data for POSTs, PUTs, DELETEs are sent as JSON.
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
      $data = json_encode(array('data' => $data));
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
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
    CURLOPT_PORT => TERMINUS_PORT,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_COOKIE => $session_data['session'],
    CURLOPT_HTTPHEADER => $headers,
  );
  curl_setopt_array($ch, $opts);

  // grab URL and pass it to the browser
  drush_log('Making api request.', 'notice');
  drush_log($url, 'notice');
  $result = curl_exec($ch);
  list($headers_text, $json) = explode("\r\n\r\n", $result, 2);
  // Work around extra 100 Continue headers - http://stackoverflow.com/a/2964710/1895669
  if(strpos($headers_text," 100 Continue") !== FALSE){
    list($headers_text, $json) = explode( "\r\n\r\n", $json , 2);
  }

  if (curl_errno($ch) != 0) {
    $error = curl_error($ch);
    curl_close($ch);
    drush_log("CONNECTION ERROR: $error", 'failed');
    return FALSE;
  }
  $info = curl_getinfo($ch);
  if ($info['http_code'] > 399) {
    drush_log('Request failed: '. $info['http_code']. ' '. $json, 'failed');
    return FALSE;
  }

  // TODO: nice to have a timer here for how long the request took.
  drush_log('Request complete.', 'notice');
  drush_log("Respnse headers:\n" .$headers_text, 'notice');
  drush_log("JSON response:\n" . $json, 'notice');
  return array('headers' => $headers_text, 'json' => $json);
}

/**
 * Clean up output so there are consistent service level names.
 */
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


/**
 * This is a curl-based tool to start a session in the dashboard.
 *
 * It doesn't follow the normal pattern since it's working off Drupal's login
 * forms directly. This will be refactored when there's a direct CLI auth
 * mechanism in the API itself.
 *
 * Many thanks to Amitai and the gang at: https://drupal.org/node/89710
 *
 */
function drush_terminus_pantheon_auth($email = FALSE) {
    // Flush existing data.
    drush_cache_clear_all('*', 'pantheon', TRUE);
    $session_data = terminus_bootstrap(FALSE);
    if (!$email) {
      $email = drush_prompt(dt('Pantheon account email address'), NULL, TRUE);
    }
    $nocache = drush_get_option('nocache');
    if (!$nocache && isset($session_data['email'])) {
      drush_log(dt('Cached auth found for !email', array('!email' => $email)), 'notice');
      if (terminus_validate_session($session_data)) {
        drush_log('Session still valid. Good to go!', 'ok');
        return TRUE;
      }
    }
    $password = drush_get_option('password');
    if (!$password) {
      $password = drush_prompt(dt('Pantheon dashboard password'), NULL, TRUE, TRUE);
    }
    drush_log(dt('Authenticating as !email', array('!email' => $email)), 'ok');
    $ch = curl_init();
    $host = TERMINUS_HOST;
    if ($session_data['onebox']) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      $host = 'onebox.getpantheon.com';
    }
    $url = 'https://'. $host .'/login';
    drush_log(dt('POSTing auth to: !url', array('!url' => $url)));
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
      return drush_set_error("Failure!", 'Authentication failed. Please check your credentials and try again.');
    }
    $session = FALSE;
    $set_cookie = explode('; ', $set_cookie_header);
    foreach ($set_cookie as $cookie) {
      if (strpos($cookie, 'SSESS') === 0) {
        $session = $cookie;
      }
    }
    if (!$session) {
      return drush_set_error("Failure!", 'Session not found. Please check your credentials and try again.');
    }

    // Grab the UUID Now
    curl_setopt($ch, CURLOPT_COOKIE, $session);
    curl_setopt($ch, CURLOPT_URL, 'https://'. $host .'/account');
    $result = curl_exec($ch);
    $location_header = terminus_parse_drupal_headers($result, 'Location');
    if (!$location_header) {
      return drush_set_error("Failure!", 'Could not determine user UUID. Please check your credentials and try again.');
    }
    $user_uuid = array_pop(explode('/', $location_header));

    drush_log('Success!', 'ok');
    drush_log(dt('User UUID: !user_uuid', array('!user_uuid' => $user_uuid)), 'notice');

    // Start socking away new data.
    $data = array('user_uuid' => $user_uuid, 'session' => $session, 'email' => $email);
    if ($session_data['onebox']) {
      // Mark this session as "forever onebox."
      $data['onebox'] = TRUE;
    }

    drush_cache_set('terminus-current-session', $data, 'pantheon');

    // Store the session in drush cache.
    // close cURL resource, and free up system resources
    curl_close($ch);
}


/**
 * Helper function for parsing Drupal headers for login.
 */
function terminus_parse_drupal_headers($result, $target_header='Set-Cookie') {
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

/**
 * Provides user prompt for missing data.
 * @param $key string Required data name. i.e. site_uuid, environment, etc..
 * @return string input data
 */
function terminus_session_select_data($key, $site_uuid = NULL, $environment = NULL, $element = NULL) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  switch ($key) {
    case 'site_uuid':
      $cid = 'terminus-sites';
      drush_log('Loading site data from Pantheon.', 'notice');
      $result = terminus_api_user_site_list($user_uuid);
      if ($result === FALSE) {
        return FALSE;
      }
      $sites = json_decode($result['json']);
      drush_cache_set($cid, $sites, 'pantheon');

      $choices = array();
      foreach ($sites as $site_uuid => $data) {
        $i = $data->information;
        $choices[$site_uuid] = array($i->name, $i->service_level, $site_uuid);
      }

      $val = drush_choice($choices, 'Select a site.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'environment':
      if ($site_uuid) {
        $request = terminus_api_site_environments($site_uuid);
        $environments = json_decode($request['json']);
        $choices = array();
        foreach ($environments as $env => $data) {
          $choices[$env] = $env;
        }
      }
      else {
        $choices = array('dev' => 'dev', 'test' => 'test', 'live' => 'live');
      }
      $val = drush_choice($choices, 'Select an environment.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'element':
      $choices = array('database' => 'database', 'files' => 'files', 'code' => 'code');
      $val = drush_choice($choices, 'Backup type.', '!key');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'bucket':
      $result = terminus_api_backup_catalog($site_uuid, $environment);
      $choices = array();
      $buckets = array();
      $backups = json_decode($result['json']);

      foreach ($backups as $id => $a) {
        $parts = explode('_', $id);

        if (!isset($a->filename) || $parts[2] != $element) {
          continue;
        }
        $size = round($a->size/(1024*1024), 2) .' MB';
        $choices[$a->filename] = $a->filename . ' ' . $size; //$type . ' ' . date(DATE_RFC822, $a->timestamp) . ' ' . $parts[2] . ' ' . $parts[0] .'_'. $parts[1] . ' ' . $size;
        $buckets[$a->filename] = $parts[0] .'_'. $parts[1];
      }
      krsort($choices);
      $val = drush_choice($choices, 'Select a backup.');
      if ($val !== FALSE) {
        return $buckets[$val];
      }
      break;

    case 'cmode':
      $choices = array(0 => 'Retrieve connection mode', 'sftp' => 'SFTP', 'git' => 'Git');
      return drush_choice($choices, 'Connection mode');
      break;

    case 'destination':
      $val = drush_prompt('Select a destination', $default = './');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'hostname':
      if ($site_uuid && $environment) {
        $result = terminus_api_site_environment_hostnames($site_uuid, $environment);
        $hostnames = json_decode($result['json']);
        $choices = array();
        foreach ($hostnames as $hostname => $data) {
          $choices[$hostname] = $hostname;
        }
        krsort($choices);
        $val = drush_choice($choices, 'Select a hostname.');
        if ($val !== FALSE) {
          return $val;
        }
      }

      break;
  }
}

/**
 * Helper function to return the "latest" bucket for a given site_uuid, environment, and element
 */
function terminus_latest_bucket($site_uuid, $environment, $element = NULL) {
  $result = terminus_api_backup_catalog($site_uuid, $environment);
  $buckets = array();
  $backups = json_decode($result['json']);
  foreach ($backups as $id => $a) {
    $parts = explode('_', $id);
    if (!isset($a->filename) || $parts[2] != $element) {
      continue;
    }
    $buckets[$a->filename] = $parts[0] .'_'. $parts[1];
  }
  krsort($buckets);
  return reset($buckets);
}

/**
 * Helper function to handle site uuid input.
 */
function terminus_site_input($site_input, $prompt = TRUE, $uuid_only = FALSE) {
  if (!$site_input && $prompt) {
    $site_input = terminus_session_select_data('site_uuid');
  }
  if (terminus_validate_uuid($site_input)) {
    return $site_input;
  }
  if (empty($site_input) || $uuid_only) {
    return FALSE;
  }
  return terminus_get_site_uuid_by_name($site_input);
}

/**
 * Helper function to return the site_uuid from the name.
 */
function terminus_get_site_uuid_by_name($site_name) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  $cid = 'terminus-sites';
  drush_log('Loading site data from Pantheon.', 'notice');
  $result = terminus_api_user_site_list($user_uuid);
  if ($result === FALSE) {
    return FALSE;
  }
  $sites = json_decode($result['json']);
  drush_cache_set($cid, $sites, 'pantheon');
  foreach ($sites as $site_uuid => $data) {
    $i = $data->information;
    if ($i->name == $site_name) {
      return $site_uuid;
    }
  }
  return FALSE;
}

/**
 * UUID Validating Regex.
 */
/**
 * Atlas UUID Generation Validation
 */
function terminus_validate_uuid($uuid) {
  return preg_match('#^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$#', $uuid);
}
