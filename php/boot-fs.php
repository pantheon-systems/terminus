<?php

/**
 * This file needs to parse without error in PHP < 5.3
 */

if (PHP_SAPI != 'cli') {
  echo "Only CLI access.\n";
  die(-1);
}

$min_version = '5.5.0';

if (version_compare(PHP_VERSION, $min_version, '<')) {
  printf(
    "Error: Terminus requires PHP %s or newer. You are running version %s.\n",
    $min_version,
    PHP_VERSION
  );
  die(-1);
}

define('TERMINUS_ROOT', dirname(__DIR__));

include TERMINUS_ROOT . '/php/terminus.php';
