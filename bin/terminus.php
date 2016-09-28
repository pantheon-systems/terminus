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

use League\Container\Container;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Terminus;
use Robo\Robo;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Terminus\Caches\FileCache;
use Terminus\Collections\Sites;

// Initializing the Terminus application
$config = new Config();
$application = new Terminus('Terminus', $config->get('version'), $config);

// Configuring the dependency-injection container
$input = new ArgvInput($_SERVER['argv']);
$output = new ConsoleOutput();
$container = Robo::createDefaultContainer($input, $output, $application, $config);

$container->share('fileCache', FileCache::class);
$container->share('session', Session::class)
    ->withArgument('fileCache');
$container->inflector(SessionAwareInterface::class)
    ->invokeMethod('setSession', ['session']);
$container->share('sites', Sites::class);
$container->inflector(SiteAwareInterface::class)
    ->invokeMethod('setSites', ['sites']);

// Running Terminus
$runner = new Runner($container);
$status_code = $runner->run($input, $output);
exit($status_code);
