<?php

namespace Terminus;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Terminus;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\TeeLogger;
use Terminus\Outputters\OutputterInterface;
use Terminus\Utils;
use Terminus\Dispatcher;
use Katzgrau\KLogger\Logger as KLogger;

class Runner {
  public $config;
  public $extra_config;

  private $arguments;
  private $assoc_args;
  private $_early_invoke = array();
  private $global_config_path;
  private $project_config_path;
  private $formatter;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var OutputterInterface
   */
  private $outputter;

  public function __construct() {
    $this->init_config();
    $this->init_colorization();
    $this->init_formatter();
    $this->init_logger();
    $this->init_outputter();
  }

  public function __get($key) {
    if ('_' === $key[0])
      return null;

    return $this->$key;
  }

  public function find_command_to_run($args) {
    $command = \Terminus::get_root_command();

    $cmd_path = array();
    while (!empty($args) && $command->can_have_subcommands()) {
      $cmd_path[] = $args[0];
      $full_name  = implode(' ', $cmd_path);

      $subcommand = $command->find_subcommand($args);

      if (!$subcommand) {
        throw new TerminusException(
          "'{cmd}' is not a registered command. See 'terminus help'.",
          array('cmd' => $full_name)
       );
      }

      $command = $subcommand;
    }

    return array($command, $args, $cmd_path);
  }

  public function run_command($args, $assoc_args = array()) {

    try {
      list($command, $final_args, $cmd_path) = $this->find_command_to_run($args);
      $name = implode(' ', $cmd_path);

      if (isset($this->extra_config[$name])) {
        $extra_args = $this->extra_config[$name];
      }
      else {
        $extra_args = array();
      }

      $command->invoke($final_args, $assoc_args, $extra_args);

    } catch (\Exception $e) {
      if (method_exists($e, 'getReplacements')) {
        $this->logger->error($e->getMessage(), $e->getReplacements());
      }
      else {
        $this->logger->error($e->getMessage());
      }
      exit(1);
    }
  }

  private function _run_command() {
    $this->run_command($this->arguments, $this->assoc_args);
  }

  public function in_color() {
    return $this->colorize;
  }

  private function init_colorization() {
    if ($this->config['colorize'] == 'auto') {
      $this->colorize = !\cli\Shell::isPiped();
    } else {
      $this->colorize = $this->config['colorize'];
    }
  }

  private function init_formatter() {
    // Pick an output formatter
    if ($this->config['json']) {
      $this->formatter = new Terminus\Outputters\JSONFormatter();
    }
    else if ($this->config['bash']) {
      $this->formatter = new Terminus\Outputters\BashFormatter();
    }
    else {
      $this->formatter = new Terminus\Outputters\PrettyFormatter();
    }
  }


  private function init_logger() {
    $this->logger = new TeeLogger();

    // Console output logger
    // Determine the logging level
    $level = LogLevel::INFO;
    if (!empty($this->config['debug'])) {
      $level = LogLevel::DEBUG;
    }
    else if (!empty($this->config['silent'])) {
      $level = LogLevel::EMERGENCY;
    }

    // Create a new outputter to write to stderr.
    $outputter = new Terminus\Outputters\Outputter(
      new Terminus\Outputters\StreamWriter('php://stderr'),
      $this->formatter
    );

    $this->logger->addLogger(
      new Terminus\Loggers\OutputLogger($outputter),
      $level
    );

    // Set up the file logger.
    $logDirectory = ini_get('error_log');
    if (isset($_SERVER['TERMINUS_LOG_DIR'])) {
      $logDirectory = $_SERVER['TERMINUS_LOG_DIR'];
    }
    if (!empty($logDirectory)) {
      // Determine the logging level
      $level = LogLevel::INFO;
      if (!empty($this->config['debug'])) {
        $level = LogLevel::DEBUG;
      }

      $this->logger->addLogger(
        new KLogger($logDirectory, $level),
        $level
      );
    }

    Terminus::set_logger($this->logger);
  }

  private function init_outputter() {
    // Create an output service.
    $this->outputter = new Terminus\Outputters\Outputter(
      new Terminus\Outputters\StreamWriter('php://stdout'),
      $this->formatter
    );

    Terminus::set_outputter($this->outputter);
  }

  private function init_config() {
    $configurator = \Terminus::get_configurator();

    // Runtime config and args
    {
      list($args, $assoc_args, $runtime_config) = $configurator->parse_args(
        array_slice($GLOBALS['argv'], 1));


      $this->arguments = $args;
      $this->assoc_args = $assoc_args;

      $configurator->merge_array($runtime_config);
    }

    list($this->config, $this->extra_config) = $configurator->to_array();
  }

  public function run() {
    if (Terminus::is_test())
      return true;

    if (empty($this->arguments))
      $this->arguments[] = 'help';

    // Load bundled commands early, so that they're forced to use the same
    // APIs as non-bundled commands.
    Utils\load_command($this->arguments[0]);

    if (isset($this->config['require'])) {
      foreach ($this->config['require'] as $path) {
        Utils\load_file($path);
      }
    }

    try {
      // Show synopsis if it's a composite command.
      $r = $this->find_command_to_run($this->arguments);
      if (is_array($r)) {
        list($command) = $r;

        if ($command->can_have_subcommands()) {
          $command->show_usage();
          exit;
        }
      }
    } catch (TerminusException $e) {
      // Do nothing. Actual error handling will be done by _run_command
    }

    // First try at showing man page
    if ('help' === $this->arguments[0] && (isset($this->arguments[1]))) {
      $this->_run_command();
    }

    # Run the stinkin command!
    $this->_run_command();
  }

}
