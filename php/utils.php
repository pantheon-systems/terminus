<?php

namespace Terminus\Utils;

/**
 * Terminus is in test mode
 *
 * @return bool
 */
function isTest() {
  $is_test = (
    (boolean)getenv('TERMINUS_TEST_MODE')
    || (boolean)getenv('TERMINUS_VCR_CASSETTE')
  );
  return $is_test;
}

/**
 * Check whether Terminus is running in a certain OS environment
 *
 * @param string $test_os The operating system to check
 * @return bool True if OS running Terminus is based on argument passed
 */
function isOs($test_os = '') {
  $test_os = strtoupper($test_os);
  $os = strtoupper(substr(PHP_OS, 0, 3));
  switch ($test_os) {
    case 'MAC':
      $is_os = ($os == 'DAR');
        break;
    case 'LINUX':
      $is_os = ($os == 'LIN');
        break;
    case 'WINDOWS':
      $is_os = ($os == 'WIN');
        break;
    default:
      $is_os = false;
  }
  return $is_os;
}

/**
 * Check whether Terminus is running in a Mac environment
 *
 * @return bool True if OS running Terminus is Mac
 */
function isMac() {
  return isOs('Mac');
}

/**
 * Check whether Terminus is running in a Linux environment
 *
 * @return bool True if OS running Terminus is Linux
 */
function isLinux() {
  return isOs('Linux');
}

/**
 * Check whether Terminus is running in a Windows environment
 *
 * @return bool True if OS running Terminus is Windows
 */
function isWindows() {
  return isOs('Windows');
}
