<?php


/**
 * API Call to Get Code Commit Information
 */
function terminus_api_code_log($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'code-log';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Get Core Status
 */
function terminus_api_code_upstream_info($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'code-upstream-updates';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to get branches.
 */
function terminus_api_code_tips($site_uuid) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'code-tips';
  $method = 'GET';

  return terminus_request($realm, $uuid, $path, $method);
}

/**
 * API Call to Update Core Status
 */
function terminus_api_code_upstream_update($site_uuid, $updatedb=FALSE, $overwrite=FALSE) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'code-upstream-updates';
  $method = 'POST';
  $data = json_encode(
    array(
      'updatedb' => $updatedb,
      'xoption' => ($overwrite) ? 'theirs' : '',
    )
  );

  return terminus_request($realm, $uuid, $path, $method, $data);
}
