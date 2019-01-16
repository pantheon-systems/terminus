<?php

/**
 * Bootstrap file for unit tests
 */

// The CL args used to initialize these tests would change how Terminus runs.
unset($GLOBALS['argv']);

require_once __DIR__ . '/../../vendor/autoload.php';

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
// @TODO: Unit tests should not rely on side effects like these. When the Config object is properly injectable this
// will not be necessary.
$home = getenv('HOME');
putenv("TERMINUS_CACHE_DIR=$home/.terminus/testcache");
