<?php

define('Terminus', true); //Can be used by plugins/themes to check if Terminus is running or not
define('TERMINUS_VERSION', '1.0.0');

$source = 'unknown';
if((PHP_SAPI == 'cli') && isset($argv)) {
  $source = explode('/', $argv[0]);
  $source = end($source);
}
define('TERMINUS_SCRIPT', $source);
date_default_timezone_set('UTC');

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/login.php';
include TERMINUS_ROOT . '/php/FileCache.php';
include TERMINUS_ROOT . '/php/dispatcher.php';
include TERMINUS_ROOT . '/php/class-terminus.php';
include TERMINUS_ROOT . '/php/class-terminus-command.php';

\Terminus\Utils\load_dependencies();

set_exception_handler('\Terminus\Utils\handle_exception'); # Set a custom exception handler

if(isset($_SERVER['TERMINUS_HOST']) && $_SERVER['TERMINUS_HOST'] != '') 
  define('TERMINUS_HOST', $_SERVER['TERMINUS_HOST']);
else
  define('TERMINUS_HOST', 'dashboard.getpantheon.com');

define('TERMINUS_PORT', '443');

if(isset($_SERVER['VCR_CASSETTE'])) {
  \VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));
  \VCR\VCR::configure()->setMode($_SERVER['VCR_MODE']);
  \VCR\VCR::turnOn();
  \VCR\VCR::insertCassette($_SERVER['VCR_CASSETTE']);
}

Terminus::get_runner()->run();

if(isset($_SERVER['VCR_CASSETTE'])) {
  \VCR\VCR::eject();
  \VCR\VCR::turnOff();
}
