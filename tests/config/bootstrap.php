<?php

/**
 * Bootstrap file for functional tests.
 */

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pantheon\Terminus\Tests\Functional\TerminusTestBase;

// create a log channel
global $log;


$log =& $GLOBALS['LOGGER'];
$log = new Logger('PHPUNIT');
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
    putenv(sprintf('TERMINUS_CACHE_DIR=%s/.terminus/testcache', getenv('HOME')));
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
            '%s auth:login --machine-token=%s',
            TERMINUS_BIN_FILE,
            $token
        )
    );
}


if (!getenv('TERMINUS_TESTING_RUNTIME_ENV')) {
    // Create a testing runtime multidev environment.
    $sitename = TerminusTestBase::getSiteName();

    // Clean up orphaned test-* multidev environments before creating a new one.
    $log->info('Checking for orphaned test-* multidev environments...');
    $listOutput = [];
    exec(
        sprintf('%s multidev:list %s --format=json', TERMINUS_BIN_FILE, $sitename),
        $listOutput,
        $listCode
    );
    if (0 === $listCode && !empty($listOutput)) {
        $multidevs = json_decode(implode('', $listOutput), true);
        if (is_array($multidevs)) {
            $testEnvs = array_filter($multidevs, function ($env, $id) {
                return str_starts_with($id, 'test-');
            }, ARRAY_FILTER_USE_BOTH);
            if (!empty($testEnvs)) {
                $log->info(sprintf('Found %d orphaned test-* multidev(s), deleting...', count($testEnvs)));
                foreach ($testEnvs as $id => $env) {
                    $log->info(sprintf('Deleting orphaned multidev: %s', $id));
                    exec(
                        sprintf('%s multidev:delete %s.%s --delete-branch --yes', TERMINUS_BIN_FILE, $sitename, $id),
                        $delOutput,
                        $delCode
                    );
                    if (0 !== $delCode) {
                        $log->warning(sprintf('Failed to delete orphaned multidev %s (exit code %d)', $id, $delCode));
                    }
                }
            }
        }
    }

    $multidev = sprintf('test-%s', substr(uniqid(), -6, 6));
    $createMdCommand = sprintf('multidev:create %s.dev %s', $sitename, $multidev);

    exec(
        sprintf('%s %s', TERMINUS_BIN_FILE, $createMdCommand),
        $output,
        $code
    );
    if (0 !== $code) {
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new Exception(
            sprintf(
                'Command "%s" exited with non-zero code (%d). Output: %s',
                $createMdCommand,
                $code,
                implode("\n", $output)
            )
        );
    }

    TerminusTestBase::setMdEnv($multidev);

    register_shutdown_function(function () use ($sitename, $multidev, $log) {
        // Delete a testing runtime multidev environment.
        // The platform occasionally returns a transient error for this command
        // (e.g. an erroneous "environment was not found" message), so retry a
        // few times before treating the failure as fatal.
        $deleteMdCommand = sprintf('multidev:delete %s.%s --delete-branch --yes', $sitename, $multidev);
        $maxAttempts = 3;
        $retryIntervalSeconds = 10;
        $code = 0;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $output = [];
            exec(
                sprintf('%s %s', TERMINUS_BIN_FILE, $deleteMdCommand),
                $output,
                $code
            );

            if (0 === $code) {
                break;
            }

            $log->warning(sprintf(
                'Command "%s" exited with non-zero code (%d) on attempt %d of %d.',
                $deleteMdCommand,
                $code,
                $attempt,
                $maxAttempts
            ));

            if ($attempt < $maxAttempts) {
                sleep($retryIntervalSeconds);
            }
        }

        if (0 !== $code) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw new Exception(sprintf(
                'Command "%s" exited with non-zero code (%d) after %d attempts',
                $deleteMdCommand,
                $code,
                $maxAttempts
            ));
        }
    });
}
