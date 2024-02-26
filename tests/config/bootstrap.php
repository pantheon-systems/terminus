<?php

/**
 * Bootstrap file for functional tests.
 */

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pantheon\Terminus\Tests\Functional\TerminusTestBase;

// create a log channel
global $log;

$log = &$GLOBALS['LOGGER'];
$log = new Logger('PHPUNIT');

global $preamble;

$preamble = "";
if (getenv('TERMINUS_VERBOSE')) {
    $preamble .= sprintf('%s --verbose ', $preamble);
}


$tokens_dir = implode(DIRECTORY_SEPARATOR, [$_SERVER['HOME'], '.terminus', 'cache', 'tokens']);
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
$log->pushHandler(new StreamHandler($testcache_dir . "/phpunit_tests.log", Logger::DEBUG));
$log->info(print_r($GLOBALS, true));
// Override the default cache directory by setting an environment variable. This prevents our tests from overwriting
// the user's real cache and session.
if (!getenv('TERMINUS_CACHE_DIR')) {
    // Set the terminus cache directory if not already set
    putenv(sprintf('TERMINUS_CACHE_DIR=%s/.terminus/testcache', $_SERVER['HOME']));
}
$cache_dir = getenv('TERMINUS_CACHE_DIR');
if (!is_dir($cache_dir)) {
    mkdir(
        $cache_dir,
        0700,
        true
    );
}

// If the bin file doesn't exist, build it.
const TERMINUS_BIN_FILE = './terminus.phar';
if (getenv('TERMINUS_ENV') == 'local') {
    $log->info('Running in local environment. Reloading plugins to avoid warnings.');
    exec(
        "./terminus.phar self:plugin:reload --yes"
    );
}
$version = exec(sprintf('%s --version', TERMINUS_BIN_FILE));
if (!file_exists(TERMINUS_BIN_FILE)) {
    exec(
        'composer pre-commit && composer phar:build && composer install --dev'
    );
    if (!file_exists(TERMINUS_BIN_FILE)) {
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new Exception('Could not build Terminus PHAR file.');
    }
}
$log->info('Using Terminus PHAR file: %s VERSION: %s', [TERMINUS_BIN_FILE, $version]);
chmod(TERMINUS_BIN_FILE, 0700);


// if there is no pre-existing session, grab the machine token
// and use it to login to create a session
if (!file_exists($cache_dir . '/session')) {
    $token = getenv('TERMINUS_TOKEN');
    if (empty($token)) {
        $dir = new DirectoryIterator($tokens_dir);
        $tokens = array_diff(
            scandir(
                $dir->getRealPath(),
                SCANDIR_SORT_DESCENDING
            ),
            ['..', '.']
        );
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
    exec(
        sprintf(
            '%s auth:login %s --machine-token=%s',
            TERMINUS_BIN_FILE,
            $preamble,
            $token
        )
    );
}

// Read a Pants JSON file to set fixtures created for this testing cycle
if (file_exists($cache_dir . "/fixtures.json")) {
    try {
        $fixtures = json_decode(
            file_get_contents($cache_dir . "/fixtures.json"),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        $log->warning('Could not read fixtures.json: %s', [$e->getMessage()]);
    }
    foreach ($fixtures as $key => $value) {
        // site_wp becomes env var TERMINUS_SITE_WP, etc
        putenv(sprintf('%s=%s', "TERMINUS_" . strtoupper($key), $value));
    }
}

if (!getenv('TERMINUS_TESTING_RUNTIME_ENV')) {
    // Create a testing runtime multidev environment.
    $sitename = TerminusTestBase::getSiteName();

    $multidev = sprintf('test-%s', substr(uniqid(), -6, 6));
    $createMdCommand = sprintf('%s multidev:create %s %s.dev %s', TERMINUS_BIN_FILE, $preamble, $sitename, $multidev);
    $log->debug('Creating multidev environment: %s', [$createMdCommand]);
    exec($createMdCommand, $output, $code);
    // Many times this process will result in a database error that is not fatal.
    // we will actually get info on the created environment to ensure it exists.
    $envInfoCommand = sprintf('%s env:info %s %s.%s --format=json', TERMINUS_BIN_FILE, $preamble, $sitename, $multidev);
    $log->debug('Creating multidev environment: %s', [$envInfoCommand]);
    exec($envInfoCommand, $output, $code);
    if (0 !== $code) {
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new Exception(sprintf('Command "%s" exited with non-zero code (%d)', $envInfoCommand, $code));
    }
    $log->info('Verified environment: %s', [$output, true]);
    TerminusTestBase::setMdEnv($multidev);

    register_shutdown_function(function () use ($sitename, $multidev) {
        global $preamble, $log;
        // Delete a testing runtime multidev environment.
        $deleteMdCommand = sprintf('multidev:delete %s %s.%s --delete-branch --yes', $preamble, $sitename, $multidev);
        $command = sprintf('%s %s', TERMINUS_BIN_FILE, $deleteMdCommand);
        $log->debug('Deleting multidev environment: %s', [$command]);
        exec(
            $command,
            $output,
            $code
        );

        if (0 !== $code) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception(sprintf('Command "%s" exited with non-zero code (%d)', $deleteMdCommand, $code));
        }
    });
}
