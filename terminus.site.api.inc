<?php

/**
 * @file
 * API functions for Site commands
 */

/**
 * API Function Get The Site Omnibus "State"
 */
function terminus_api_site_state($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'state';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method, $data);
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
function terminus_api_site_put_service_level($site_uuid, $level) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'service-level';
  $method = 'PUT';
  $data = $level;
  $result = terminus_request($realm, $uuid, $path, $method, $data);
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
  * API Function to Set One Site Attribute
 */
function terminus_api_site_attribute_set($site_uuid, $attribute, $value) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'attributes/' . $attribute;
  $method = 'PUT';
  $data = $value;
  return terminus_request($realm, $uuid, $path, $method, $data);
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
function terminus_api_site_team_member_invite($email, $site_uuid, $invited_by) {
    $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team/' . $email;
    $method = 'POST';
    $data = json_encode(array('invited_by' => $invited_by));
    return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
  * API Function to Add a User to a Site Team
  */
function terminus_api_site_team_member_add($site_uuid, $user_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'team/' . $user_uuid;
  $method = 'PUT';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
  * API Function to Delete a User from a Site Team
  */
function terminus_api_site_team_member_delete($site_uuid, $user_uuid) {
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
 * API Function Get Events
 */
function terminus_api_site_events($site_uuid, $env, $limit = 100) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/events/?limit=' . $limit;
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
function terminus_api_site_clearcache($site_uuid, $env) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env. '/clear-cache';
  $method = 'POST';
  $data = '';
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Get On Server Development Status
 */
function terminus_api_site_environment_onserverdev_get($site_uuid, $env) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/on-server-development';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Set Server Development Status
 */
function terminus_api_site_environment_onserverdev_set($site_uuid, $env, $onserverdevstatus) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/on-server-development';
  $method = 'PUT';
  $data = array('enabled' => $onserverdevstatus);
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Do a Git Commit
 */
function terminus_api_site_environment_onserverdev_commit($site_uuid, $env, $message, $user_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/on-server-development/commit';
  $method = 'POST';
  $data = ($message) ? array('message' => $message, 'user' => $user_uuid) : NULL;
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Get a Git Diff of a path
 */
function terminus_api_site_environment_onserverdev_diff_get($site_uuid, $env, $file_path = "") {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/on-server-development/diff?path=' . rawurlencode($file_path);
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Get a Git Diff --numstat
 */
function terminus_api_site_environment_onserverdev_diff_stats_get($site_uuid, $env) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/on-server-development/diffstat';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to Lock an Environment
 */
function terminus_api_site_environment_lock($site_uuid, $env, $username, $password) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/lock';
  $method = 'PUT';
  $data = array('username' => $username, 'password' => $password);

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Delete a Lock on an Environment
 */
function terminus_api_site_environment_lock_delete($site_uuid, $env) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/lock';
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to get hostnames for an Environment
 */
function terminus_api_site_environment_hostnames($site_uuid, $env) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $env . '/hostnames';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Function to get environments for an site!
 */
function terminus_api_site_environments($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}
