<?php

/**
 * Bootstrap file for functional tests.
 */

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;

$tokens_dir = implode(DIRECTORY_SEPARATOR, [$_SERVER['HOME'], '.terminus', 'cache' , 'tokens']);
if (!is_dir($tokens_dir)) {
    mkdir(
        $tokens_dir,
        0700,
        true
    );
}

$testcache_dir = implode(DIRECTORY_SEPARATOR, [$_SERVER['HOME'], '.terminus', 'testcache']);
if (!is_dir($testcache_dir)) {
    mkdir(
        $testcache_dir,
        0700,
        true
    );
}

// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
putenv(sprintf('TERMINUS_CACHE_DIR=%s/.terminus/testcache', getenv('HOME')));

$token = getenv('TERMINUS_TOKEN');
if (empty($token)) {
    $dir = new DirectoryIterator($tokens_dir);
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
        putenv(sprintf('TERMINUS_TOKEN=%s', $tokenData->token));
    }
}

const TERMINUS_BIN_FILE = './t3';
chmod(TERMINUS_BIN_FILE, 0700);

if ($token) {
    exec(
        sprintf(
            '%s auth:login --machine-token=%s',
            TERMINUS_BIN_FILE,
            $token
        )
    );
}

if (!getenv('TERMINUS_TESTING_RUNTIME_ENV')) {
    // Create a testing runtime multidev environment.
    $sitename = TerminusTestTrait::getSiteName();
    $multidev = substr(uniqid('test-'), 0, 11);
    $createMdCommand = sprintf('multidev:create %s.dev %s', $sitename, $multidev);
    exec(
        sprintf('%s %s', TERMINUS_BIN_FILE, $createMdCommand),
        $output,
        $code
    );
    if (0 !== $code) {
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new Exception(sprintf('Command "%s" exited with non-zero code (%d)', $createMdCommand, $code));
    }

    TerminusTestTrait::setMdEnv($multidev);

    register_shutdown_function(function () use ($sitename, $multidev) {
        // Delete a testing runtime multidev environment.
        $deleteMdCommand = sprintf('multidev:delete %s.%s --delete-branch --yes', $sitename, $multidev);
        exec(
            sprintf('%s %s', TERMINUS_BIN_FILE, $deleteMdCommand),
            $output,
            $code
        );

        if (0 !== $code) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception(sprintf('Command "%s" exited with non-zero code (%d)', $deleteMdCommand, $code));
        }
    });
}
