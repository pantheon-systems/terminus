<?php
/**
 * Bootstrap file for unit tests
 */
define('CLI_ROOT', dirname(__DIR__) );
define('TERMINUS_CMD','php '.CLI_ROOT.'/php/boot-fs.php');
putenv('CLI_TEST_MODE=1');
require_once CLI_ROOT.'/php/boot-fs.php';
Terminus::set_config('nocache',TRUE);
Terminus::set_config('debug',false);
use Terminus\Fixtures;
use Terminus\Session;

// Set some dummy credentials
Session::setData(json_decode('{
    "user_uuid": "0ffec038-4410-43d0-a404-46997f672d7a",
    "session": "0ffec038-4410-43d0-a404-46997f672d7a%3A68486878-dd87-11e4-b243-bc764e1113b5%3AbQR2fyNMh5PQXN6F2Ewge",
    "session_expire_time": 1739299351,
    "email": "bensheldon+pantheontest@gmail.com"
}'));

\VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));

# Prevent API requests from being made in CI Environment
$ci_environment = getenv( 'CI' );
if ($ci_environment) {
  \VCR\VCR::configure()->setMode('none');
}
