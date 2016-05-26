<?php

namespace Terminus\Utils;

/**
 * Check whether Terminus is running in a Linux environment
 *
 * @return bool True if OS running Terminus is Linux
 */
function isLinux() {
  $is_linux = isOs('Linux');
  return $is_linux;
}

/**
 * Check whether Terminus is running in a Mac environment
 *
 * @return bool True if OS running Terminus is Mac
 */
function isMac() {
  $is_mac = isOs('Mac');
  return $is_mac;
}

/**
 * Determines whether Terminus is operating on a Onebox currently
 *
 * @return bool True if the targeted host is a Onebox
 */
function isOnebox() {
  $is_onebox = (strpos(TERMINUS_HOST, 'onebox') !== false);
  return $is_onebox;
}

/**
 * Check whether Terminus is running in a certain OS environment
 *
 * @param string $test_os The operating system to check
 * @return bool True if OS running Terminus is based on argument passed
 */
function isOs($test_os = '') {
  $test_os = strtoupper($test_os);
  $os      = strtoupper(substr(PHP_OS, 0, 3));
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
 * Terminus is in test mode
 *
 * @return bool
 */
function isTest() {
  $is_test = ((boolean)TERMINUS_TEST_MODE || (boolean)TERMINUS_VCR_CASSETTE);
  return $is_test;
}

/**
 * Check whether Terminus is running in a Windows environment
 *
 * @return bool True if OS running Terminus is Windows
 */
function isWindows() {
  $is_windows = isOs('Windows');
  return $is_windows;
}
