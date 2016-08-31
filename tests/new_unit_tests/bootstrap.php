<?php
/**
 * Bootstrap file for unit tests
 */

require_once __DIR__ . '/../../vendor/autoload.php';

\VCR\VCR::configure()->enableRequestMatchers(['method', 'url', 'body',]);
