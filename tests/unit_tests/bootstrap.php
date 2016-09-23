<?php

/**
 * Bootstrap file for unit tests
 */

// The CL args used to initialize these tests would change how Terminus runs.
unset($GLOBALS['argv']);

define('TERMINUS_LOG_DIR', '/tmp/');

require_once __DIR__ . '/../../vendor/autoload.php';

use VCR\VCR;

VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);
VCR::configure()->setMode('none');
