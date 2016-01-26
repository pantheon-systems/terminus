<?php

/**
 * Bootstrap file for unit tests
 */

unset($GLOBALS['argv']);

$_SERVER['TERMINUS_LOG_DIR'] = '/tmp/';
define('CLI_ROOT', dirname(__DIR__) . '/..');
define('TEST_DIR', dirname(__DIR__));
define('TERMINUS_CMD', 'php ' . CLI_ROOT . '/php/boot-fs.php');
putenv('CLI_TEST_MODE=1');

require_once CLI_ROOT . '/vendor/autoload.php';
require_once CLI_ROOT . '/php/boot-fs.php';
$runner = new \Terminus\Runner(array('debug' => false));
use Terminus\Session;

$session_id  = '0ffec038-4410-43d0-a404-46997f672d7a%3A68486878';
$session_id .= '-dd87-11e4-b243-bc764e1113b5%3AbQR2fyNMh5PQXN6F2Ewge';
// Set some dummy credentials
Session::setData(
  json_decode(
    '{
      "user_uuid": "0ffec038-4410-43d0-a404-46997f672d7a",
      "session": "' . $session_id . '",
      "session_expire_time": 1739299351,
      "email": "bensheldon+pantheontest@gmail.com"
    }'
  )
);

\VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));

// Prevent API requests from being made in CI Environment
$ci_environment = getenv('CI');
if ($ci_environment) {
  \VCR\VCR::configure()->setMode('none');
}
