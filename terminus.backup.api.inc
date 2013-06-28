<?php

/**
 * API call to get the backup catalog.
 */
function terminus_api_backup_catalog($site_uuid, $environment) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/'. $environment .'/backups/catalog';
  $method = 'GET';
  $data = NULL;
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API call to get a single backup item's S3 token
 */
function terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/'. $environment .'/backups/catalog/'. $bucket .'/'. $element .'/s3token';
  $method = 'POST';
  $data = array('method' => 'GET');
  return terminus_request($realm, $uuid, $path, $method, $data);
}

/**
 * API Function to make a backup.
 */
function terminus_api_site_make_backup($site_uuid, $environment, $entry_type = 'backup', $code = TRUE, $db = TRUE, $files = TRUE) {
  $realm = 'site';
  $uuid = $site_uuid;
  $path = 'environments/' . $environment . '/backups/create';
  $method = 'POST';
  $data = array(
      'entry_type' => $entry_type,
      'scheduled_for' => time(),
      'code' => ($code) ? 1 : 0,
      'database' => ($db) ? 1 : 0,
      'files' => ($files) ? 1 : 0,
  );
  return terminus_request($realm, $uuid, $path, $method, $data);
}
