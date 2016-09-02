#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Pantheon\Terminus\Terminus;

$terminus = new Terminus();
$command_factory = new AnnotatedCommandFactory();
//$formatter_manager = new FormatterManager();

$discovery = new CommandFileDiscovery();
$command_files = $discovery->discover(__DIR__, '\Commands');
foreach ($command_files as $command_class) {
    $command_instance = new $command_class(['config' => $this->config,]);
  //$command_factory->commandProcessor()->setFormatterManager($formatter_manager);
    $command_list = $command_factory->createCommandsFromClass($command_instance);
    foreach ($command_list as $command) {
        $terminus->add($command);
    }
}

$terminus->run();
