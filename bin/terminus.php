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

$container = new \League\Container\Container();
$input = new \Symfony\Component\Console\Input\ArgvInput($_SERVER['argv']);
$output = new \Symfony\Component\Console\Output\ConsoleOutput();
\Robo\Robo::configureContainer($container, $input, $output, $app);

$config = new Config();
$terminus = new Terminus('Terminus', $config->get('version'), $config);
$runner = new Runner(['application' => $terminus, 'config' => $config, 'container' => $container]);
$status_code = $runner->run($input, $output);
exit($status_code);
