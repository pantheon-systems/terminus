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
  public function __construct($config = array()) {
    $this->setConfigurator();
    $this->setConfig($config);
    $params          = array(
      'runner' => $this,
    );
    $params          = array_merge($this->config, $params);
    $this->terminus  = new Terminus($params);
    $this->logger    = Terminus::getLogger();
  }

  /**
   * Retrieves properties requested
   *
   * @param string $key Property name to return
   * @return mixed
   */
  public function __get($key) {
    if (($key[0] == '_') || (!isset($this->$key))) {
      return null;
    }
    return $this->$key;
  }

  /**
   * Identifies the command to be run
   *
   * @param array $args The non-hyphenated (--) terms from the CL
   * @return array
   *   0 => [Terminus\Dispatcher\RootCommand]
   *   1 => [array] args
   *   2 => [array] command path
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
   * Retrieves the configurator property
   *
   * @return Configurator
   */
  public function getConfigurator() {
    return $this->configurator;
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

    // Load bundled commands early, so that they're forced to use the same
    // APIs as non-bundled commands.
    Utils\loadCommand($this->arguments[0]);

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
      // Do nothing. Actual error-handling will be done by _runCommand
      $this->logger->debug($e->getMessage());
    }

    // First try at showing man page
    if (($this->arguments[0] == 'help') && (isset($this->arguments[1]))) {
      $this->_runCommand();
    }

    $this->_runCommand();
  }

  /**
   * Runs a command
   *
   * @param array $args       The non hyphenated (--) terms from the CL
   * @param array $assoc_args The hyphenated terms from the CL
   * @return void
   */
  public function runCommand($args, $assoc_args = array()) {
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
      exit(1);
    }
  }

  /**
   * Runs a command via runCommand by supplying it with properties as args
   *
   * @return void
   */
  private function _runCommand() {
    $this->runCommand($this->arguments, $this->assoc_args);
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

  /**
   * Sets the configurator property
   *
   * @param Configurator|null $configurator Configurator object to set
   * @return void
   */
  private function setConfigurator(Configurator $configurator = null) {
    if (is_null($configurator)) {
      $this->configurator = new Configurator(
        TERMINUS_ROOT . '/php/config-spec.php'
      );
    } else {
      $this->configurator = $configurator;
    }
  }

}
