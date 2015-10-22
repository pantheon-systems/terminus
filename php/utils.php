<?php

namespace Terminus\Utils;

use Terminus;
use Terminus\Iterators\Transform;
use ArrayIterator;

if (!defined('JSON_PRETTY_PRINT')) {
  define('JSON_PRETTY_PRINT', 128);
}

/**
 * Composes associative arguments into a command string
 *
 * @param [array] $assoc_args Arguments for command line in array form
 * @return [string] $return Command string form of param
 */
function assocArgsToStr($assoc_args) {
  $return = '';

  foreach ($assoc_args as $key => $value) {
    if ($value === true) {
      $return .= " --$key";
    } else {
      $return .= " --$key=" . escapeshellarg($value);
    }
  }

  return $return;
}

/**
 * Ensures that the given destination is valid
 *
 * @param [string]  $destination Location of directory to ensure viability of
 * @param [boolean] $make        True to create destination if it does not exist
 * @return [string] $destination Same as the parameter
 */
function destinationIsValid($destination, $make = true) {
  if (file_exists($destination) AND !is_dir($destination)) {
    throw new TerminusException(
      'Destination given is a file. It must be a directory.'
    );
  }

  if (!is_dir($destination)) {
    if (!$make) {
      $make = Terminus::confirm("Directory does not exists. Create it now?");
    }
    if ($make) {
      mkdir($destination, 0755);
    }
  }

  return $destination;
}

/**
 * Get file name from a URL
 *
 * @param [string] $url A valid URL
 * @return [string] The file name from the given URL
 */
function getFilenameFromUrl($url) {
  $path     = parseUrl($url);
  $parts    = explode('/', $path['path']);
  $filename = end($parts);
  return $filename;
}

/**
 * Return an array of paths where vendor autoload files may be located
 *
 * @return [array] $vendor_paths
 */
function getVendorPaths() {
  $vendor_paths = array(
    TERMINUS_ROOT . '/../../../vendor',
    TERMINUS_ROOT . '/vendor'
  );
  return $vendor_paths;
}

/**
 * Checks given path for whether it is absolute
 *
 * @param [string] $path Path to check
 * @return [boolean] $is_root True if path is absolute
 */
function isPathAbsolute($path) {
  $is_root = (isset($path[1]) && ($path[1] == ':') || ($path[0] == '/'));
  return $is_root;
}

/**
 * Checks whether email is in a valid or not
 *
 * @param [string] $email String to be evaluated for email address format
 * @return [boolean] $is_email True if $email is in email address format
 */
function isValidEmail($email) {
  $is_email = !is_bool(filter_var($email, FILTER_VALIDATE_EMAIL));
  return $is_email;
}

/**
 * Check whether Terminus is running in a Windows environment
 *
 * @return [boolean] $is_windows True if OS running Terminus is Windows
 */
function isWindows() {
  $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
  return $is_windows;
}

/**
 * Includes every command file in the commands directory
 *
 * @return [void]
 */
function loadAllCommands() {
  $cmd_dir = TERMINUS_ROOT . '/php/commands';

  $iterator = new \DirectoryIterator($cmd_dir);

  foreach ($iterator as $filename) {
    if (substr($filename, -4) != '.php') {
      continue;
    }

    include_once "$cmd_dir/$filename";
  }
}

/**
 * Includes a single command file
 *
 * @param [string] $name Of command of which the class file will be included
 * @return [void]
 */
function loadCommand($name) {
  $path = TERMINUS_ROOT . "/php/commands/$name.php";

  if (is_readable($path)) {
    include_once $path;
  }
}

/**
 * Requires inclusion of Composer's autoload file
 *
 * @return [void]
 */
function loadDependencies() {
  if (strpos(TERMINUS_ROOT, 'phar:') === 0) {
    require TERMINUS_ROOT . '/vendor/autoload.php';
    return;
  }

  $has_autoload = false;

  foreach (getVendorPaths() as $vendor_path) {
    if (file_exists($vendor_path . '/autoload.php')) {
      require $vendor_path . '/autoload.php';
      $has_autoload = true;
      break;
    }
  }

  if (!$has_autoload) {
    fputs(STDERR, "Internal error: Can't find Composer autoloader.\n");
    exit(3);
  }
}

/**
 * Using require() directly inside a class grants access to private methods
 * to the loaded code
 *
 * @param [string] $path Path to the file to be required
 * @return [void]
 */
function loadFile($path) {
  require $path;
}

/**
 * Render PHP or other types of files using Mustache templates
 * IMPORTANT: Automatic HTML escaping is disabled!
 *
 * @param [string] $template_name File name of the template to be used
 * @param [array]  $data          Context to pass through for template use
 * @return [string] $rendered_template The rendered template
 */
function mustacheRender($template_name, $data) {
  if (!file_exists($template_name)) {
    $template_name = TERMINUS_ROOT . "/templates/$template_name";
  }

  $template = file_get_contents($template_name);

  $mustache = new \Mustache_Engine(
    array(
      'escape' => function ($val) {
        return $val;
      }
    )
  );
  $rendered_template = $mustache->render($template, $data);
  return $rendered_template;
}

/**
 * Takes a host string such as from wp-config.php and parses it into an array
 *
 * @param [string] $raw_host MySQL host string, as defined in wp-config.php
 * @return [array] $assoc_args Connection inforrmation for MySQL
 */
function mysqlHostToCliArgs($raw_host) {
  $assoc_args = array();

  $host_parts = explode(':', $raw_host);
  if (count($host_parts) == 2) {
    list($assoc_args['host'], $extra) = $host_parts;
    $extra = trim($extra);
    if (is_numeric($extra)) {
      $assoc_args['port']     = intval($extra);
      $assoc_args['protocol'] = 'tcp';
    } elseif ($extra !== '') {
      $assoc_args['socket'] = $extra;
    }
  } else {
    $assoc_args['host'] = $raw_host;
  }

  return $assoc_args;
}

/**
 * Parses a URL and returns its components
 *
 * @param [string] $url URL to parse
 * @return [array] $url_parts An array of URL components
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
 * @param [string] $string String to be sanitized
 * @return [string] $name Param string, sanitized
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
 * @param [string] $filename Name of file from which to remove ".gz"
 * @return [string] $file Param string, ".gz" removed
 */
function sqlFromZip($filename) {
  $file = preg_replace('#\.gz$#s', '', $filename);
  return $file;
}
