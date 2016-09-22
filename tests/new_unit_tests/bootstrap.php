<?php
/**
 * Bootstrap file for unit tests
 */

require_once __DIR__ . '/../../vendor/autoload.php';

\VCR\VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);


// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
// @TODO: Unit tests should not rely on side effects like these. When the Config object is properly injectable this
// will not be necessary.
putenv("TERMINUS_CACHE_DIR=~/.terminus/testcache");
