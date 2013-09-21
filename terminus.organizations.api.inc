<?php
/**
 * @file
 * API functions for Organizations in Userspace.
 */

/**
 * API Call to Get a User Organizations.
 */
function terminus_api_user_organizations($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * The following API calls are only available to organization admins.
 */

/**
 * API Call to get sites within an Organizations.
 */
function terminus_api_user_organization_sites($user_uuid, $organization_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/sites';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to out a site into an Organizations.
 */
function terminus_api_user_organization_site_add($user_uuid, $organization_uuid, $site_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/sites/'. $site_uuid;
  $method = 'PUT';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to remove a site from an Organizations.
 */
function terminus_api_user_organization_site_remove($user_uuid, $organization_uuid, $site_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/sites/'. $site_uuid;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to get users within an organizetion.
 */
function terminus_api_user_organization_users($user_uuid, $organization_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/users';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to add a user to an organizetion.
 *
 * TODO: promote/demote
 */
function terminus_api_user_organization_user_add($user_uuid, $organization_uuid, $user_to_add, $admin = FALSE) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/users/'. $user_to_add;
  if ($admin) {
    $path .= '?admin=1';
  }
  $method = 'PUT';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to remove a user from an organizetion.
 */
function terminus_api_user_organization_user_remove($user_uuid, $organization_uuid, $user_to_delete) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/users/'. $user_to_delete;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

