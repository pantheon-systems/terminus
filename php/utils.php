<?php

namespace Terminus\Utils;

/**
 * Terminus is in test mode
 *
 * @return bool
 */
function isTest() {
  $is_test = (
    (boolean)getenv('CLI_TEST_MODE')
    || (boolean)getenv('VCR_CASSETTE')
  );
  if ((boolean)getenv('TERMINUS_TEST_IGNORE')) {
    $is_test = !$is_test;
  }
  return $is_test;
}

/**
 * Check whether Terminus is running in a Windows environment
 *
 * @return bool True if OS running Terminus is Windows
 */
function isWindows() {
  $is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
  if ((boolean)getenv('TERMINUS_TEST_IGNORE')) {
    $is_windows = !$is_windows;
  }
  return $is_windows;
}