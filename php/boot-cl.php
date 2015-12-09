<?php

if ((PHP_SAPI == 'cli') && isset($argv)) {
  $source = explode('/', $argv[0]);
  $source = end($source);
  define('TERMINUS_SCRIPT', $source);
}

include TERMINUS_ROOT . '/php/utils.php';
include TERMINUS_ROOT . '/php/dispatcher.php';

\Terminus\Utils\loadDependencies();

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
