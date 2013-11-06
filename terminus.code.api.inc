<?php

/**
 * API Call to Get Code Commit Information
 */
function terminus_api_code_log($site_uuid) {
  $realm = 'site';
  $path = 'code-log';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API Call to Get Core Status
 */
function terminus_api_code_upstream_info($site_uuid) {
  $realm = 'site';
  $path = 'code-upstream-updates';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API Call to get branches.
 */
function terminus_api_code_tips($site_uuid) {
  $realm = 'site';
  $path = 'code-tips';
  $method = 'GET';

  return terminus_request($realm, $site_uuid, $path, $method);
}

/**
 * API Call to create branch.
 */
function terminus_api_code_branch_create($site_uuid, $branch_name) {
  $realm = 'site';
  $path = 'code-branch';
  $method = 'POST';
  $data = array(
    'refspec' => 'refs/heads/' . $branch_name,
  );

  return terminus_request($realm, $site_uuid, $path, $method, $data);
}

/**
 * API Call to delete branch.
 */
function terminus_api_code_branch_delete($site_uuid, $branch_name) {
  $realm = 'site';
  $path = 'code-branch';
  $method = 'DELETE';
  $data = array(
    'refspec' => 'refs/heads/' . $branch_name,
  );

  return terminus_request($realm, $site_uuid, $path, $method, $data);
}

/**
 * API Call to Update Core Status
 */
function terminus_api_code_upstream_update($site_uuid, $updatedb = FALSE, $overwrite = FALSE) {
  $realm = 'site';
  $path = 'code-upstream-updates';
  $method = 'POST';
  $data = array(
    'updatedb' => $updatedb,
    'xoption' => $overwrite ? 'theirs' : '',
  );

  return terminus_request($realm, $site_uuid, $path, $method, $data);
}
