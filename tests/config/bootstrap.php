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

    // Generate unique multidev name scoped to this CI run to prevent concurrent runs from interfering
    $runId = getenv('GITHUB_RUN_ID');
    if (!$runId) {
        // Fallback for local runs: use username + timestamp
        $username = getenv('USER') ?: 'local';
        $runId = substr($username, 0, 5) . substr((string)time(), -5);
    }
    // Max multidev name length is 11 chars, prefix with 't' for 'test'
    $multidev = 't' . substr($runId, -10, 10);
    $log->info(sprintf('Will create multidev: %s (run ID: %s)', $multidev, $runId));

    // Clean up orphaned test-* multidev environments before creating a new one.
    // Only delete envs older than 24 hours to avoid trampling concurrent CI runs.
    $log->info('Checking for orphaned test-* multidev environments...');
    $ageCutoff = getenv('TERMINUS_TEST_ORPHAN_AGE_HOURS') ?: 24;
    $cutoffTimestamp = time() - ($ageCutoff * 3600);
    $listOutput = [];
    exec(
        sprintf('%s multidev:list %s --format=json', TERMINUS_BIN_FILE, $sitename),
        $listOutput,
        $listCode
    );
    if (0 === $listCode && !empty($listOutput)) {
        $multidevs = json_decode(implode('', $listOutput), true);
        if (is_array($multidevs)) {
            // Filter for test multidevs matching our naming scheme (t + 10 chars)
            // and older than the cutoff to avoid trampling concurrent CI runs
            $testEnvs = array_filter($multidevs, function ($env, $id) use ($cutoffTimestamp) {
                if (!str_starts_with($id, 't') || strlen($id) !== 11) {
                    return false;
                }
                $created = $env['created'] ?? 0;
                return is_numeric($created) && (int) $created < $cutoffTimestamp;
            }, ARRAY_FILTER_USE_BOTH);
            if (!empty($testEnvs)) {
                $log->info(sprintf('Found %d orphaned test multidev(s), deleting...', count($testEnvs)));
                foreach ($testEnvs as $id => $env) {
                    $ageHours = round((time() - ($env['created'] ?? 0)) / 3600, 1);
                    $log->info(sprintf('Deleting orphaned multidev: %s (age: %s hours)', $id, $ageHours));
                    exec(
                        sprintf('%s multidev:delete %s.%s --delete-branch --yes', TERMINUS_BIN_FILE, $sitename, $id),
                        $delOutput,
                        $delCode
                    );
                    if (0 !== $delCode) {
                        $outputStr = implode(' ', $delOutput);
                        // 404 errors are fine - env already deleted
                        if (strpos($outputStr, 'was not found') !== false || strpos($outputStr, '404') !== false) {
                            $log->info(sprintf('Multidev %s already deleted (404)', $id));
                        } else {
                            $log->warning(
                                sprintf(
                                    'Failed to delete orphaned multidev %s (exit code %d): %s',
                                    $id,
                                    $delCode,
                                    $outputStr
                                )
                            );
                        }
                        // Don't throw - cleanup failure shouldn't block test execution
                    }
                }
            }
        }
    }

    // Wake the source environment before creating multidev to prevent "Failed to wake" errors
    $log->info('Ensuring source environment is awake...');
    exec(
        sprintf('%s env:wake %s.dev --quiet 2>&1', TERMINUS_BIN_FILE, $sitename),
        $wakeOutput,
        $wakeCode
    );
    if (0 !== $wakeCode) {
        $log->warning('Failed to wake dev environment, proceeding anyway (site may be waking)...');
    } else {
        $log->info('Source environment is awake');
    }
    // No sleep needed - if wake succeeded, site is ready; if failed, multidev:create will handle wake itself

    // Create multidev with retry logic to handle transient failures
    $createMdCommand = sprintf('multidev:create %s.dev %s', $sitename, $multidev);
    $maxAttempts = 3;
    $output = [];
    $code = 1;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $log->info(sprintf('Creating multidev %s (attempt %d/%d)...', $multidev, $attempt, $maxAttempts));

        exec(
            sprintf('%s %s 2>&1', TERMINUS_BIN_FILE, $createMdCommand),
            $output,
            $code
        );

        if (0 === $code) {
            $log->info(sprintf('Multidev %s created successfully', $multidev));
            break;
        }

        $log->warning(
            sprintf(
                'Attempt %d/%d failed (exit code %d). Output: %s',
                $attempt,
                $maxAttempts,
                $code,
                implode("\n", $output)
            )
        );

        if ($attempt < $maxAttempts) {
            $log->info('Retrying immediately...');
            $output = []; // Reset output array for next attempt
        }
    }

    if (0 !== $code) {
        /** @noinspection PhpUnhandledExceptionInspection */
        throw new Exception(
            sprintf(
                'Failed to create multidev %s after %d attempts. Last exit code: %d. Output: %s',
                $multidev,
                $maxAttempts,
                $code,
                implode("\n", $output)
            )
        );
    }

    TerminusTestBase::setMdEnv($multidev);

    register_shutdown_function(function () use ($sitename, $multidev, $log) {
        // Delete a testing runtime multidev environment.
<<<<<<< HEAD
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
=======
        $log->info(sprintf('Cleaning up multidev: %s', $multidev));
        $deleteMdCommand = sprintf('multidev:delete %s.%s --delete-branch --yes', $sitename, $multidev);
        exec(
            sprintf('%s %s 2>&1', TERMINUS_BIN_FILE, $deleteMdCommand),
            $output,
            $code
        );

        if (0 !== $code) {
            $outputStr = implode(' ', $output);
            // 404 errors are fine - env may have been manually deleted or never created
            if (strpos($outputStr, 'was not found') !== false || strpos($outputStr, '404') !== false) {
                $log->info(sprintf('Multidev %s not found during cleanup (already deleted)', $multidev));
            } else {
                $log->warning(
                    sprintf(
                        'Failed to delete multidev %s (exit code %d): %s',
                        $multidev,
                        $code,
                        $outputStr
                    )
                );
            }
            // Don't throw - cleanup failure shouldn't break test reporting
        } else {
            $log->info(sprintf('Multidev %s deleted successfully', $multidev));
>>>>>>> ae5d3fd2 (test: improve shutdown cleanup error handling and logging)
        }
    });
}
