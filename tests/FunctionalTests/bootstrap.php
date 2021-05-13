<?php

/**
 * Bootstrap file for functional tests
 */

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
$home = getenv('HOME');
putenv("TERMINUS_CACHE_DIR=$home/.terminus/testcache");
$existing = getenv('TERMINUS_SITE');
if (empty($existing)) {
    putenv("TERMINUS_SITE=ci-terminus-generated-" . uniqid());
}
putenv("PANTHEON_INTERNAL_ORG=5ae1fa30-8cc4-4894-8ca9-d50628dcba17");

