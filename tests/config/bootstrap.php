<?php

/**
 * Bootstrap file for functional tests
 */

$tokens_dir = $_SERVER['HOME'] .
    DIRECTORY_SEPARATOR . ".terminus" . DIRECTORY_SEPARATOR .
    "cache" . DIRECTORY_SEPARATOR . "tokens";
if (!is_dir($tokens_dir)) {
    mkdir(
        $tokens_dir,
        0700,
        true
    );
}

$tokens_dir = $_SERVER['HOME'] .
    DIRECTORY_SEPARATOR . ".terminus" . DIRECTORY_SEPARATOR .
    "testcache";
if (!is_dir($tokens_dir)) {
    mkdir(
        $tokens_dir,
        0700,
        true
    );
}

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
$home = getenv('HOME');
putenv("TERMINUS_CACHE_DIR=$home/.terminus/testcache");

$token = getenv('TERMINUS_TOKEN');

if (empty($token)) {
    $dir = new DirectoryIterator(
        $_SERVER['HOME'] . DIRECTORY_SEPARATOR . ".terminus" .
        DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "tokens"
    );
    $tokens = array_diff(scandir(
        $dir->getRealPath(),
        SCANDIR_SORT_DESCENDING
    ), ['..', '.']);
    if (count($tokens)) {
        $token = array_shift($tokens);
        $tokenData = json_decode(
            file_get_contents(
                $dir->getRealPath() . DIRECTORY_SEPARATOR . $token
            ),
            false,
            JSON_THROW_ON_ERROR
        );
        putenv("TERMINUS_TOKEN={$tokenData->token}");
    }
}


define('TERMINUS_BIN_FILE', './t3');

chmod(TERMINUS_BIN_FILE, 0700);

if ($token) {
    exec(
        sprintf(
            "%s auth:login --machine-token=%s",
            TERMINUS_BIN_FILE,
            $token
        )
    );
}
