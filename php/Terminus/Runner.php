<?php

namespace Terminus;

use Terminus;
use Terminus\Utils;
use Terminus\Loggers\Logger;
use Terminus\Exceptions\TerminusException;

class Runner {
  public $config;
  public $extra_config;

  private $arguments;
  private $assoc_args;
  private $colorize;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var OutputterInterface
   */
  private $outputter;

  /**
   * Constructs object. Initializes config, colorizaiton, loger, and outputter
   *
   * @return [Runner] $this
   */
  public function __construct() {
    $this->initConfig();
    $this->initColorizaiton();
    $this->initLogger();
    $this->initOutputter();
  }

  /**
   * Retrieves properties requested
   *
   * @param [string] $key Property name to return
   * @return [mixed] $this->$key
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
   * @param [array] $args The non-hyphenated (--) terms from the CL
   * @return [array] $command_array
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
          array('cmd' => $full_name)
        );
      }

      $command = $subcommand;
    }

    $command_array = array($command, $args, $cmd_path);
    return $command_array;
  }

  /**
   * Determines if output is to be colorized
   *
   * @return [boolean] $this->colorize
   */
  public function inColor() {
    return $this->colorize;
  }

  /**
   * Runs the Terminus command
   *
   * @return [void]
   */
  public function run() {
    if (!Terminus::isTest() && empty($this->arguments)) {
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
   * @param [array] $args       The non hyphenated (--) terms from the CL
   * @param [array] $assoc_args The hyphenated terms from the CL
   * @return [void]
   */
  public function runCommand($args, $assoc_args = array()) {
    try {
      list($command, $final_args, $cmd_path) = $this->findCommandToRun($args);
      $name = implode(' ', $cmd_path);

      if (isset($this->extra_config[$name])) {
        $extra_args = $this->extra_config[$name];
      } else {
        $extra_args = array();
      }

      $command->invoke($final_args, $assoc_args, $extra_args);

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
   * @return [void]
   */
  private function _runCommand() {
    $this->runCommand($this->arguments, $this->assoc_args);
  }

  /**
   * Initializes colorization and saves to Runner property
   *
   * @return [void]
   */
  private function initColorizaiton() {
    if ($this->config['colorize'] == 'auto') {
      $this->colorize = !\cli\Shell::isPiped();
    } else {
      $this->colorize = $this->config['colorize'];
    }
  }

  /**
   * Initializes configurator, saves config data to it
   *
   * @return [void]
   */
  private function initConfig() {
    $configurator = Terminus::getConfigurator();

    // Runtime config and args
    list($args, $assoc_args, $runtime_config) = $configurator->parseArgs(
      array_slice($GLOBALS['argv'], 1)
    );

    $this->arguments  = $args;
    $this->assoc_args = $assoc_args;

    $configurator->mergeArray($runtime_config);

    list($this->config, $this->extra_config) = $configurator->toArray();
  }

  /**
   * Initializes logger and saves it to Terminus property
   *
   * @return [void]
   */
  private function initLogger() {
    $this->logger = new Logger(array('config' => $this->config));
    Terminus::setLogger($this->logger);
  }

  /**
   * Initializes outputter and saves it to Terminus property
   *
   * @return [void]
   */
  private function initOutputter() {
    // Pick an output formatter
    if ($this->config['format'] == 'json') {
      $formatter = new Terminus\Outputters\JSONFormatter();
    } elseif ($this->config['format'] == 'bash') {
      $formatter = new Terminus\Outputters\BashFormatter();
    } else {
      $formatter = new Terminus\Outputters\PrettyFormatter();
    }

    // Create an output service.
    $this->outputter = new Terminus\Outputters\Outputter(
      new Terminus\Outputters\StreamWriter('php://stdout'),
      $formatter
    );

    Terminus::setOutputter($this->outputter);
  }

}
