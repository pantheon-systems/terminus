<?php
/**
 * Bootstrap file for unit tests
 */
define('CLI_ROOT', dirname(__DIR__) );
define('TERMINUS_CMD','php '.CLI_ROOT.'/php/boot-fs.php');
putenv('CLI_TEST_MODE=1');
putenv('BUILD_FIXTURES=0');
putenv('USE_FIXTURES=1');
require_once CLI_ROOT.'/php/boot-fs.php';
use Terminus\Fixtures;
use Terminus\Session;
