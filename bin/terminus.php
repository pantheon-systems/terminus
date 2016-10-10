#!/usr/bin/env php
<?php

$phar_path = \Phar::running(true);
if ($phar_path) {
    include_once "$phar_path/vendor/autoload.php";
} else {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        include_once __DIR__ . '/../vendor/autoload.php';
    } elseif (file_exists(__DIR__ . '/../../autoload.php')) {
        include_once __DIR__ . '/../../autoload.php';
    }
}

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Terminus;
use Robo\Robo;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

// Initializing the Terminus application
$config = new Config();
$application = new Terminus('Terminus', $config->get('version'), $config);

// Configuring the dependency-injection container
$input = new ArgvInput($_SERVER['argv']);
$output = new ConsoleOutput();
$container = Robo::createDefaultContainer($input, $output, $application, $config);

// Running Terminus
$runner = new Runner($container);
$status_code = $runner->run($input, $output);
exit($status_code);
