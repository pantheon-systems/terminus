<?php

/**
 * Bootstrap file for unit tests
 */

// The CL args used to initialize these tests would change how Terminus runs.
unset($GLOBALS['argv']);

define('TERMINUS_TEST_MODE', true);
define('TERMINUS_LOG_DIR', '/tmp/');

require_once __DIR__ . '/../../vendor/autoload.php';

use VCR\VCR;

VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);
VCR::configure()->setMode('none');

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
// @TODO: Unit tests should not rely on side effects like these. When the Config object is properly injectable this
// will not be necessary.
putenv("TERMINUS_CACHE_DIR=~/.terminus/testcache");
