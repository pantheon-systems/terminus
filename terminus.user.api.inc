<?php
/**
 * @file
 * API functions for User Integration
 */

/**
 * API Call to Get One User's Data
 */
function terminus_api_user_get($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = '';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Get a User Profile
 */
function terminus_api_user_profile_get($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'profile';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Set a User Profile
 */
function terminus_api_user_profile_set($user_uuid, $profile) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'profile';
  $method = 'PUT';
  $data = $profile;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Update/Patch a User Profile
 */
function terminus_api_user_profile_update($user_uuid, $profile) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'profile';
  $method = 'PATCH';
  $data = $profile;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Get a Field from a User Profile
 */
function terminus_api_user_profile_get_attribute($user_uuid, $attribute) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'profile/' . $attribute;
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Set a Field from a User Profile
 */
function terminus_api_user_profile_set_attribute($user_uuid, $attribute, $value) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'profile/' . $attribute;
  $method = 'PUT';
  $data = $value;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Set a User's Password
 */
function terminus_api_user_password_set($user_uuid, $password) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'password';
  $method = 'PUT';
  $data = $password;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Get a User's Email
 * Note - No logging!
 */
function terminus_api_user_email_get($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'email';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Get a User's UUID
 * NOTE - No logging!
 */
function terminus_api_user_uuid_get($email) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = rawurlencode($email) . '/uuid';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Check a User's Email by Email
 * NOTE - No logging!
 */
function terminus_api_user_email_check_by_email($email) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = rawurlencode($email) . '/email';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}


/**
 * API Call to Set a User's Email
 */
function terminus_api_user_email_set($user_uuid, $email) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'email';
  $method = 'PUT';
  $data = $email;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Delete a User
 */
function terminus_api_user_delete($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Get a User's SSH Keys
 */
function terminus_api_user_sshkey_get($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'keys';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Delete a User's SSH Keys
 */
function terminus_api_user_sshkey_delete_all($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'keys';
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Add a SSH Key to a User
 */
function terminus_api_user_sshkey_add($user_uuid, $sshkey) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'keys';
  $method = 'POST';
  $data = $sshkey;

  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Call to Delete a Specific SSH for a User
 */
function terminus_api_user_sshkey_delete_key($user_uuid, $sshkey_fingerprint) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'keys/' . $sshkey_fingerprint;
  $method = 'DELETE';

  return terminus_request($realm, $uuid, $path, $method);
}


/**
 * API Call to Add and Validate SSH Key
 */
function terminus_api_sshkey_validate($user_uuid, $sshkey) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'keys?validate=true';
  $method = 'POST';
  $data = $sshkey;

  return terminus_request($realm, $uuid, $path, $method, $data);
}


/**
  * API Function to Get List of Sites a User is a Team Member of
  */
function terminus_api_user_site_list($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'sites';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to get sites owned by a user.
 */
function terminus_api_user_owned_list($user_uuid) {
  $realm = 'user';
  $uuid = $user_uuid;
  $path = 'sites?owned=1';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API call to create a site.
 */
function terminus_api_user_site_create($user_uuid, $name, $label, $organization = '', $product = '') {
  $realm = 'user';
  $path = 'sites';
  $method = 'POST';
  $data = array(
    'name' => $name,
    'label' => $label,
  );
  if ($organization) {
    $data['organization'] = $organization;
  }
  if ($product) {
    $data['product'] = $product;
  }

  return terminus_request($realm, $user_uuid, $path, $method, $data);
}