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
use Pantheon\Terminus\DataStore\FileStore;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Terminus;
use Robo\Robo;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Terminus\Collections\Sites;



// Initializing the Terminus application
$config = new Config();
$application = new Terminus('Terminus', $config->get('version'), $config);

// Configuring the dependency-injection container
$input = new ArgvInput($_SERVER['argv']);
$output = new ConsoleOutput();
$container = Robo::createDefaultContainer($input, $output, $application, $config);


$container->share('dataStore', FileStore::class)
    ->withArgument(new League\Container\Argument\RawArgument($config->get('cache_dir')));
$container->share('session', Session::class)
    ->withArgument('dataStore');
$container->inflector(SessionAwareInterface::class)
    ->invokeMethod('setSession', ['session']);


$factory = $container->get('commandFactory');
$factory->setIncludeAllPublicMethods(false);

// Running Terminus
$runner = new Runner($container);
$status_code = $runner->run($input, $output);
exit($status_code);
