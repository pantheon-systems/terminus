<?php

if ((PHP_SAPI == 'cli') && isset($argv)) {
  $source = explode('/', $argv[0]);
  $source = end($source);
  define('TERMINUS_SCRIPT', $source);
}

loadDependencies();

//Set a custom exception handler
//set_exception_handler('\Terminus\Utils\handle_exception');

if (isset($_SERVER['VCR_CASSETTE'])) {
  \VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));
  \VCR\VCR::configure()->setMode($_SERVER['VCR_MODE']);
  \VCR\VCR::turnOn();
  \VCR\VCR::insertCassette($_SERVER['VCR_CASSETTE']);
}

if (isset($GLOBALS['argv'])) {
  $runner = new \Terminus\Runner();
  $runner->run();
}

if (isset($_SERVER['VCR_CASSETTE'])) {
  \VCR\VCR::eject();
  \VCR\VCR::turnOff();
}

/**
 * Return an array of paths where vendor autoload files may be located
 *
 * @return array
 */
function getVendorPaths() {
  $vendor_paths = [
    TERMINUS_ROOT . '/../../../vendor',
    TERMINUS_ROOT . '/vendor'
  ];
  return $vendor_paths;
}

/**
 * Requires inclusion of Composer's autoload file
 *
 * @return void
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