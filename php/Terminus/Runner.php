<?php

namespace Terminus;

use Terminus;
use Terminus\Utils;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;

class Runner {
  /**
   * @var array
   */
  private $arguments;
  /**
   * @var array
   */
  private $assoc_args;
  /**
   * @var array
   */
  private $config;
  /**
   * @var Configurator
   */
  private $configurator;
  /**
   * @var Logger
   */
  private $logger;
  /**
   * @var Terminus
   */
  private $terminus;

  /**
   * Constructs object. Initializes config, colorization, loger, and outputter
   *
   * @param array $config Extra settings for the config property
   */
  public function __construct(array $config = []) {
    $this->configurator = new Configurator();
    $this->setConfig($config);
    $this->terminus = new Terminus($this->config);
    $this->logger   = Terminus::getLogger();
  }

  /**
   * Identifies the command to be run
   *
   * @param array $args The non-hyphenated (--) terms from the CL
   * @return array
   *   0 => [Terminus\Dispatcher\RootCommand]
   *   1 => [array] args
   *   2 => [array] command path
   * @throws TerminusException
   */
  public function findCommandToRun($args) {
    $command = Terminus::getRootCommand();

    $cmd_path = array();
    while (!empty($args) && $command->canHaveSubcommands()) {
      $cmd_path[] = $args[0];
      $full_name  = implode(' ', $cmd_path);

      $subcommand = $command->findSubcommand($args);

      if (!$subcommand) {
        throw new TerminusException(
          "'{cmd}' is not a registered command. See 'terminus help'.",
          array('cmd' => $full_name),
          1
        );
      }

      $command = $subcommand;
    }

    $command_array = array($command, $args, $cmd_path);
    return $command_array;
  }

  /**
   * Runs the Terminus command
   *
   * @return void
   */
  public function run() {
    if (empty($this->arguments)) {
      $this->arguments[] = 'help';
    }

    if (isset($this->config['require'])) {
      foreach ($this->config['require'] as $path) {
        Utils\loadFile($path);
      }
    }

    try {
      // Show synopsis if it's a composite command.
      $r = $this->findCommandToRun($this->arguments);
      if (is_array($r)) {
        /** @var \Terminus\Dispatcher\RootCommand $command */
        list($command) = $r;

        if ($command->canHaveSubcommands()) {
          $command->showUsage();
          exit;
        }
      }
    } catch (TerminusException $e) {
      $this->logger->debug($e->getMessage());
    }

    $this->runCommand();
  }

  /**
   * Runs a command
   *
   * @return void
   */
  private function runCommand() {
    $args       = $this->arguments;
    $assoc_args = $this->assoc_args;
    try {
      /** @var \Terminus\Dispatcher\RootCommand $command */
      list($command, $final_args, $cmd_path) = $this->findCommandToRun($args);
      $name = implode(' ', $cmd_path);

      $command->invoke($final_args, $assoc_args);
    } catch (\Exception $e) {
      if (method_exists($e, 'getReplacements')) {
        $this->logger->error($e->getMessage(), $e->getReplacements());
      } else {
        $this->logger->error($e->getMessage());
      }
      exit($e->getCode());
    }
  }

  /**
   * Initializes configurator, saves config data to it
   *
   * @param array $config Config options to set explicitly
   * @return void
   */
  private function setConfig($config = array()) {
    $args = array('terminus', '--debug');
    if (isset($GLOBALS['argv'])) {
      $args = $GLOBALS['argv'];
    }

    // Runtime config and args
    list($args, $assoc_args, $runtime_config) = $this->configurator->parseArgs(
      array_slice($args, 1)
    );

    $this->arguments  = $args;
    $this->assoc_args = $assoc_args;

    $this->configurator->mergeArray($runtime_config);

    $this->config = array_merge($this->configurator->toArray(), $config);
  }

}
