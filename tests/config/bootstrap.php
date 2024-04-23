<?php

/**
 * Bootstrap file for functional tests.
 */

// create a log channel
global $log;
global $app;




// If the bin file doesn't exist, build it.
define("TERMINUS_BIN_FILE", $app->getTerminusBinary());
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
