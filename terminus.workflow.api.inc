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
 * API Function to deploy code to a site environment.
 */
function atlas_api_environment_deploy_code($site_uuid, $newenv, $updatedb = 0) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='/environments/' . $newenv . '/code';
  $method = 'POST';
  $data = json_encode(array(
    'updatedb' => $updatedb,
  ));
  terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Import Site URL
 */
function atlas_api_environment_site_import($site_uuid, $importurl, $environment, $code, $db, $files) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='/environments/' . $environment . '/import';
  $method = 'POST';
  $data = json_encode(array(
    'url' => $importurl,
    'code' => ($code) ? 1 : 0,
    'database' => ($db) ? 1 : 0,
    'files' => ($files) ? 1 : 0,
  ));
  terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Make a New Site
 */
function atlas_api_environment_site_new($site_uuid, $giturl, $environment, $branch = 'master') {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='/environments/' . $environment . '/code-init';
  $method = 'POST';
  $data = json_encode(
    array(
      'url' => $giturl,
      'branch' => $branch,
    )
  );
  terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to Wipe an Environment
 */
function atlas_api_environment_content_wipe($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path ='/environments/' . $environment . '/wipe';
  $method = 'POST';
  $data = '';
  terminus_request($realm, $uuid, $path, $method, $data);
}
