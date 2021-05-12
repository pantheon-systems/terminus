<?php

/**
 * Bootstrap file for functional tests
 */

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
$home = getenv('HOME');
putenv("TERMINUS_CACHE_DIR=$home/.terminus/testcache");
