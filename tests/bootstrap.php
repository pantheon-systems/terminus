<?php
/**
 * Bootstrap file for unit tests
 */
define('CLI_ROOT', dirname(__DIR__) );
define('CLI_TEST_MODE', TRUE);
require_once CLI_ROOT.'/php/boot-fs.php';
use Terminus\Fixtures;
use Terminus\Session;

// here's a little jujitzu to get a mock session in place
$args = $GLOBALS['argv'];
$GLOBALS['argv'] = array(__FILE__, 'auth', 'login', 'wink+behat@getpantheon.com','--password=chicago77');
Session::setData( Fixtures::get('response') );
$GLOBALS['argv'] = $args;
