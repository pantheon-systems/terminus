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

use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;
use Terminus\Models\Auth;
use Terminus\Runner;
use Terminus\Session;

\VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));
setDummyCredentials();

// Prevent API requests from being made in CI Environment
$ci_environment = getenv('CI');
if ($ci_environment) {
  \VCR\VCR::configure()->setMode('none');
}

$output_file_name  = '/tmp/output';
$moved_file_suffix = 'testmoved';

setTerminusOutputter($output_file_name);

/**
 * Returns the username and password for Behat fixtures
 *
 * @return string[]
 */
function getBehatCredentials() {
  $creds = [
    'username' => 'devuser@pantheon.io',
    'password' => 'password1',
    'token'    => 'EAJ08XqjxTbXD125qU9L5HSqlDdl9UnWHqpdB1nmt5f1x',
  ];
  return $creds;
}

/**
 * Parses the loction and name of the Terminus log file
 *
 * @return string
 */
function getLogFileName() {
  $file_name = $_SERVER['TERMINUS_LOG_DIR'] . 'log_' . date('Y-m-d') . '.txt';
  return $file_name;
}

function getLogger() {
  static $logger;
  if (!isset($logger)) {
    $logger = new Logger(
      ['config' => ['debug' => false, 'format' => 'normal']]
    );
  }
  return $logger;
}

/**
 * Logs in with Behad credentials to enable Behat fixture use
 *
 * @return void
 */
function logInWithBehatCredentials() {
  $creds   = getBehatCredentials();
  $auth    = new Auth();
  $auth->logInViaUsernameAndPassword($creds['username'], $creds['password']);
}

/**
 * Removes the named file and replaces it with the previously moved file
 *
 * @param string $file_name Name of the file to remove and replace
 * @return void
 */
function resetOutputDestination($file_name) {
  $moved_file_suffix = '.testmoved';
  if (file_exists($file_name)) {
    exec("rm -r $file_name");
  }
  if (file_exists($file_name.$moved_file_suffix)) {
    exec("mv $file_name.$moved_file_suffix $file_name");
  }
}

/**
 * Retrieves the content of the named file
 *
 * @param string $file_name Name of the file to retrieve the contents of
 * @return string
 */
function retrieveOutput($file_name = '/tmp/output') {
  if (!file_exists($file_name)) {
    throw new TerminusException('File "{file}" does not exist.', ['file' => $file_name]);
  }
  $output = file_get_contents($file_name);
  return $output;
}

/**
 * Moves the file of this name and creates a new file with the same name
 *
 * @param string $file_name Name of the file to remove and create
 * @return void
 */
function setOutputDestination($file_name) {
  $moved_file_suffix = '.testmoved';
  if (file_exists($file_name)) {
    exec("mv $file_name $file_name.$moved_file_suffix");
  }
  exec("touch $file_name");
}

/**
 * Sets some dummy credentials for this test run
 *
 * @return void
 */
function setDummyCredentials() {
  $session_id  = '0ffec038-4410-43d0-a404-46997f672d7a%3A68486878';
  $session_id .= '-dd87-11e4-b243-bc764e1113b5%3AbQR2fyNMh5PQXN6F2Ewge';
  // Set some dummy credentials
  Session::setData(
    json_decode(
      '{
        "user_uuid": "0ffec038-4410-43d0-a404-46997f672d7a",
        "session": "' . $session_id . '",
        "session_expire_time": ' . strtotime('+8 days') . ',
        "email": "bensheldon+pantheontest@gmail.com"
      }'
    )
  );
}

/**
 * Sets the Terminus outputter to a specific format and destination
 *
 * @param string $format      Type of formatter to set on outputter
 * @param string $destination Where output will be written to
 * @return void
 */
function setTerminusOutputter($destination = 'php://stdout', $format = null) {
  Runner::setOutputter($format, $destination);
}
