<?php
/**
 * @file
 * API functions for Atlas/Ygg Workflow Integration
 */

/**
 * API Function to Move Databases Around
 */
function atlas_api_environment_database_move($site_uuid, $oldenv, $newenv, $updatedb = 0) {
  $path = '/sites/' . $site_uuid . '/environments/' . $newenv . '/database';
  $method = 'POST';
  $data = json_encode(array('clone-from-environment' => $oldenv, 'updatedb' => $updatedb));
  return atlas_api($path, $method, $data);
}

/**
 * API Function to Move Files Around
 */
function atlas_api_environment_files_move($site_uuid, $oldenv, $newenv) {
  $path = '/sites/' . $site_uuid . '/environments/' . $newenv . '/files';
  $method = 'POST';
  $data = json_encode(array('clone-from-environment' => $oldenv));;
  return atlas_api($path, $method, $data);
}

/**
 * API Function to Move Code Around
 */
function atlas_api_environment_code_move($site_uuid, $newenv, $updatedb = 0) {
  $path = ($updatedb) ? '/sites/' . $site_uuid . '/environments/' . $newenv . '/code' : '/sites/' . $site_uuid . '/environments/' . $newenv . '/code';
  $method = 'POST';
  $data = json_encode(array('updatedb' => $updatedb));
  return atlas_api($path, $method, $data);
}

/**
 * API Function to Import Site URL
 */
function atlas_api_environment_site_import($site_uuid, $importurl, $environment, $code, $db, $files) {
  $path = '/sites/' . $site_uuid . '/environments/' . $environment . '/import';
  $method = 'POST';
  $data = json_encode(
    array(
      'url' => $importurl,
      'code' => ($code) ? 1 : 0,
      'database' => ($db) ? 1 : 0,
      'files' => ($files) ? 1 : 0,
    )
  );
  return atlas_api($path, $method, $data);
}

/**
 * API Function to Make a New Site
 */
function atlas_api_environment_site_new($site_uuid, $giturl, $environment, $branch = 'master') {
  $path = '/sites/' . $site_uuid . '/environments/' . $environment . '/code-init';
  $method = 'POST';
  $data = json_encode(
    array(
      'url' => $giturl,
      'branch' => $branch,
    )
  );
  return atlas_api($path, $method, $data);
}

/**
 * API Function to Wipe an Environment
 */
function atlas_api_environment_content_wipe($site_uuid, $environment) {
  $path = '/sites/' . $site_uuid . '/environments/' . $environment . '/wipe';
  $method = 'POST';
  $data = '';
  return atlas_api($path, $method, $data);

}
