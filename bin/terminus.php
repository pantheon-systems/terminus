#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Terminus;
use Pantheon\Terminus\Config;

$config = new Config();
$application = new Terminus($config);
$runner = new Runner([], $application);
$runner->run();
