<?php
/**
 * @file
 * API functions for Atlas/Ygg Workflow Integration
 */

/**
 * API call to clone a database between site environments.
 */
function terminus_api_environment_clone_database($site_uuid, $env_source, $env_target, $updatedb = 0) {
  $realm = 'site';
  $path = 'environments/' . $env_target . '/database';
  $method = 'POST';
  $uuid = $site_uuid;
  $data = array(
    'clone-from-environment' => $env_source,
    'updatedb' => $updatedb,
  );
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API call to clone user files between site environments.
 */
function terminus_api_environment_clone_files($site_uuid, $env_source, $env_target) {
  $realm = 'site';
  $path = 'environments/' . $env_target . '/files';
  $method = 'POST';
  $uuid = $site_uuid;
  $data = array(
    'clone-from-environment' => $env_source,
  );
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API call to deploy code to a site environment.
 */
function terminus_api_environment_deploy_code($site_uuid, $env_target, $updatedb = 0, $cacheclear = 0) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='environments/' . $env_target . '/code';
  $method = 'POST';
  $data = array(
    'updatedb' => $updatedb,
    'cacheclear' => $cacheclear,
  );
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API call to wipe a site environment.
 */
function terminus_api_environment_content_wipe($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='environments/' . $environment . '/wipe';
  $method = 'POST';
  $data = '';
  return terminus_request($realm, $uuid, $path, $method, $data);
}
