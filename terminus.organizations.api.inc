<?php
/**
 * @file
 * API functions for Organizations in Userspace.
 */

/**
 * API call to get a user's organizations.
 */
function terminus_api_user_organizations($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to get sites within an organization.
 *
 * Available only to organization admins.
 */
function terminus_api_user_organization_sites($user_uuid, $organization_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/sites';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to add a site into an organization.
 *
 * Available only to organization admins.
 */
function terminus_api_user_organization_site_add($user_uuid, $organization_uuid, $site_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/' . $organization_uuid . '/sites/' . $site_uuid;
  $method = 'PUT';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to remove a site from an organization.
 *
 * Available only to organization admins.
 */
function terminus_api_user_organization_site_remove($user_uuid, $organization_uuid, $site_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/sites/'. $site_uuid;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to get users within an organization.
 *
 * Available only to organization admins.
 */
function terminus_api_user_organization_users($user_uuid, $organization_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/users';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to add a user to an organization.
 *
 * Available only to organization admins.
 *
 * @todo: promote/demote
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
 * API call to remove a user from an organization.
 *
 * Available only to organization admins.
 */
function terminus_api_user_organization_user_remove($user_uuid, $organization_uuid, $user_to_delete) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'organizations/'. $organization_uuid .'/users/'. $user_to_delete;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}
