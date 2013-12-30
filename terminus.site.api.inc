<?php

/**
 * @file
 * API functions for Site commands
 */

/**
 * API Function Get The Quick Site Info
 */
function terminus_api_site_info($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = '';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}


/**
 * API Function Get The Site Omnibus "State"
 */
function terminus_api_site_state($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'state';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
  * API Function to Delete a Site
  */
function terminus_api_site_delete($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = '';
  $method = 'DELETE';
  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to get site settings.
 */
function terminus_api_site_get_settings($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'settings';
  $method = 'GET';
  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to get site service level.
 */
function terminus_api_site_get_service_level($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'service-level';
  $method = 'GET';
  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to set the site service level.
 *
 * NOTE: This can cost you money!
 */
function terminus_api_site_put_service_level($site_uuid, $service_level) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'service-level';
  $method = 'PUT';
  $data = $service_level;
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Set All Site Attributes At Once
 */
function terminus_api_site_attributes_set($site_uuid, $attributes) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'attributes';
  $method = 'PUT';
  $data = $attributes;
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to set a site attribute.
 */
function terminus_api_site_attribute_set($site_uuid, $attribute, $data) {
  $realm = 'site';
  $path = 'attributes/' . $attribute;
  $method = 'PUT';
  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API Function to Get Site Upstream
 */
function terminus_api_site_uptream_get($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'code-upstream';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get Site Attributes
 */
function terminus_api_site_attributes_get($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'attributes';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get One Site Attribute
 */
function terminus_api_site_attribute_get($site_uuid, $attribute) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'attributes/' . $attribute;
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
  * API Function to Delete Site Attributes
  */
function terminus_api_site_attributes_delete($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'attributes';
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Invite a User to Pantheon and add to Site Team
 */
function terminus_api_site_team_member_invite($site_uuid, $email, $invited_by) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team/' . rawurlencode($email);
  $method = 'POST';
  $data = array('invited_by' => $invited_by);
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
  * API Function to Delete a User from a Site Team
  */
function terminus_api_site_team_member_remove($site_uuid, $user_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team/' . $user_uuid;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get List of Users from a Site Team
 */
function terminus_api_site_team_get($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Check user against the List of Users from a Site Team
 */
function terminus_api_site_team_is_member($site_uuid, $user_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team/' . $user_uuid;
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get the user_uuid of the Site Owner
 */
function terminus_api_site_owner($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'owner';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get the user_uuid of the Site Owner
 */
function terminus_api_set_site_owner($site_uuid, $new_owner_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'owner';
  $method = 'PUT';
  $data = $new_owner_uuid;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Take Site Screenshots
 */
function terminus_api_site_screenshots($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'generate-screenshots';
  $method = 'POST';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API function to import a drush site archive.
 */
function terminus_api_site_import_drush_archive($site_uuid, $url) {
  $realm = 'site';
  $path = 'environments/dev/import';
  $method = 'POST';
  $data = array(
    'url' => $url,
    'drush_archive' => 1,
  );

  return terminus_request($realm, $site_uuid, $path, $method, $data);
}

/**
 * API Function Get Events
 */
function terminus_api_site_events($site_uuid, $environment, $limit = 100) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/events/?limit=' . $limit;
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to get bindings for a site
 */
function terminus_api_site_environment_bindings($site_uuid, $environment, $type = NULL) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/'. $environment .'/bindings';
  if ($type) {
    $path .= '?type='. urlencode($type);
  }
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}


/**
 * API Function to Clear Caches
 */
function terminus_api_site_clearcache($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment. '/clear-cache';
  $method = 'POST';
  $data = '';
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Get On Server Development Status
 */
function terminus_api_site_environment_onserverdev_get($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/on-server-development';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Set Server Development Status
 */
function terminus_api_site_environment_onserverdev_set($site_uuid, $environment, $onserverdevstatus) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/on-server-development';
  $method = 'PUT';
  $data = array('enabled' => $onserverdevstatus);
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Do a Git Commit
 */
function terminus_api_site_environment_onserverdev_commit($site_uuid, $environment, $message, $user_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/on-server-development/commit';
  $method = 'POST';
  $data = ($message) ? array('message' => $message, 'user' => $user_uuid) : NULL;
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API call to get a git diff of a path.
 */
function terminus_api_site_environment_onserverdev_diff_get($site_uuid, $environment, $file_path = "") {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/on-server-development/diff?path=' . rawurlencode($file_path);
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to get a git diff.
 */
function terminus_api_site_environment_onserverdev_diff_stats_get($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/on-server-development/diffstat';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to lock an environment.
 */
function terminus_api_site_environment_lock($site_uuid, $environment, $username, $password) {
  $realm = 'site';
  $path = 'environments/' . $environment . '/lock';
  $method = 'PUT';
  $data = array('username' => $username, 'password' => $password);

  return terminus_request($realm, $site_uuid, $path, $method, $data);
}

/**
 * API call to delete a lock on an environment.
 */
function terminus_api_site_environment_lock_delete($site_uuid, $environment) {
  $realm = 'site';
  $path = 'environments/' . $environment . '/lock';
  $method = 'DELETE';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to get hostnames for an environment.
 */
function terminus_api_site_environment_hostnames($site_uuid, $environment) {
  $realm = 'site';
  $path = 'environments/' . $environment . '/hostnames';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to add a hostname to an environment.
 */
function terminus_api_site_environment_add_hostname($site_uuid, $environment, $hostname) {
  $realm = 'site';
  $path = 'environments/' . $environment . '/hostnames/' . rawurlencode($hostname);
  $method = 'PUT';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to remove a hostname from an environment.
 */
function terminus_api_site_environment_delete_hostname($site_uuid, $environment, $hostname) {
  $realm = 'site';
  $path = 'environments/' . $environment . '/hostnames/' . rawurlencode($hostname);
  $method = 'DELETE';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to get environments for a site.
 */
function terminus_api_site_environments($site_uuid) {
  $realm = 'site';
  $path = 'environments';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to create an environment.
 */
function terminus_api_site_environment_create($site_uuid, $environment) {
  $realm = 'site';
  $path = 'environments/' . $environment;
  $method = 'POST';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to delete an environment.
 */
function terminus_api_site_environment_delete($site_uuid, $environment) {
  $realm = 'site';
  $path = 'environments/' . $environment;
  $method = 'DELETE';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to get notifications for a site to track job status.
 */
function terminus_api_site_notifications($site_uuid) {
  $realm = 'site';
  $path = 'notifications';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API call to get all bindings for a site to track spin-up status.
 */
function terminus_api_site_bindings($site_uuid) {
  $realm = 'site';
  $path = 'bindings';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API Function to get jobs for a site.
 */
function terminus_api_site_get_jobs($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'jobs';
  $method = 'GET';
  return terminus_request($realm, $uuid, $path, $method);
}
