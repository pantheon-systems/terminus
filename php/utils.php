<?php

namespace Terminus\Utils;

use ArrayIterator;
use Terminus\Caches\FileCache;
use Terminus\Commands\TerminusCommand;
use Terminus\Dispatcher;
use Terminus\Dispatcher\CompositeCommand;
use Terminus\DocParser;
use Terminus\Exceptions\TerminusException;
use Terminus\Helpers\Input;
use Terminus\Request;
use Terminus\Session;

if (!defined('JSON_PRETTY_PRINT')) {
  define('JSON_PRETTY_PRINT', 128);
}

/**
 * Ensures that the given destination is valid
 *
 * @param string $destination Location of directory to ensure viability of
 * @param bool   $make        True to create destination if it does not exist
 * @return string Same as the parameter
 * @throws TerminusException
 */
function destinationIsValid($destination, $make = true) {
  if (file_exists($destination) AND !is_dir($destination)) {
    throw new TerminusException(
      'Destination given is a file. It must be a directory.'
    );
  }

  if (!is_dir($destination)) {
    if ($make) {
      mkdir($destination, 0755);
    }
  }

  return $destination;
}

/**
 * Get file name from a URL
 *
 * @param string $url A valid URL
 * @return string The file name from the given URL
 */
function getFilenameFromUrl($url) {
  $path     = parseUrl($url);
  $parts    = explode('/', $path['path']);
  $filename = end($parts);
  return $filename;
}

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

/**
 * Loads a file of the given name from the assets directory.
 *
 * @param string $file Relative file path from the assets dir
 * @return string Contents of the asset file
 * @throws TerminusException
 */
function loadAsset($file) {
  $asset_location = sprintf('%s/assets/%s', TERMINUS_ROOT, $file);
  /**
   * The warning reporting is disabled because missing files will both issue
   * warnings and return false, and we cannot just catch the warning such as
   * things are currently set.
   */
  error_reporting(E_ALL ^ E_WARNING);
  $asset_file = file_get_contents($asset_location);
  error_reporting(E_ALL);

  if (!$asset_file) {
    throw new TerminusException(
      'Terminus could not locate an asset file at {asset_location}',
      compact('asset_location'),
      1
    );
  }
  return $asset_file;
}

/**
 * Using require() directly inside a class grants access to private methods
 * to the loaded code
 *
 * @param string $path Path to the file to be required
 * @return void
 */
function loadFile($path) {
  require $path;
}

/**
 * Parses a URL and returns its components
 *
 * @param string $url URL to parse
 * @return array An array of URL components
 */
function parseUrl($url) {
  $url_parts = parse_url($url);

  if (!isset($url_parts['scheme'])) {
    $url_parts = parse_url('http://' . $url);
  }

  return $url_parts;
}

/**
 * Sanitize the site name field
 *
 * @param string $string String to be sanitized
 * @return string Param string, sanitized
 */
function sanitizeName($string) {
  $name = $string;
  // squash whitespace
  $name = trim(preg_replace('#\s+#', ' ', $name));
  // replace spacers with hyphens
  $name = preg_replace("#[\._ ]#", "-", $name);
  // crush everything else
  $name = strtolower(preg_replace("#[^A-Za-z0-9-]#", "", $name));
  return $name;
}

/**
 * Removes ".gz" from a filename
 *
 * @param string $filename Name of file from which to remove ".gz"
 * @return string Param string, ".gz" removed
 */
function sqlFromZip($filename) {
  $file = preg_replace('#\.gz$#s', '', $filename);
  return $file;
}

/**
  * Strips sensitive data out of the JSON printed in a request string
  *
  * @param array $request_data An array of request parameters to censor
  * @param array $blacklist    Array of string keys to remove from request
  * @return string Sensitive data-stripped version of $request_data
  */
function stripSensitiveData($request_data, $blacklist = []) {
  foreach ($request_data as $key => $value) {
    if (in_array($key, $blacklist)) {
      $request_data[$key] = '*****';
    } else if (is_array($value)) {
      $request_data[$key] = stripSensitiveData($value, $blacklist);
    }
  }
  return $request_data;
}

