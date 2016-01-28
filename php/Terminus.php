<?php

use Terminus\Dispatcher;
use Terminus\DocParser;
use Terminus\FileCache;
use Terminus\Runner;
use Terminus\Session;
use Terminus\Utils;
use Terminus\Exceptions\TerminusException;
use Terminus\Loggers\Logger;
use Terminus\Outputters\OutputterInterface;

/**
 * Various utilities for Terminus commands.
 */
class Terminus {
  /**
   * @var Logger
   */
  private static $logger;
  /**
   * @var array
   */
  private static $options;
  /**
   * @var OutputterInterface
   */
  private static $outputter;
  /**
   * @var Runner
   */
  private static $runner;

  /**
   * Object constructor. Sets properties.
   *
   * @param array $arg_options Options to override defaults
   */
  public function __construct(array $arg_options = array()) {
    $default_options = array(
      'runner'   => null,
      'colorize' => 'auto',
      'format'   => 'json',
      'debug'    => false,
      'yes'      => false,
    );
    self::$options = $options = array_merge($default_options, $arg_options);

    $this->setRunner($options['runner']);
    $this->setLogger($options);
    $this->setOutputter($options['format']);
  }

  /**
   * Add a command to the terminus list of commands
   *
   * @param string $name  The name of the command that will be used in the CLI
   * @param string $class The command implementation
   * @return void
   * @throws TerminusException
   */
  public static function addCommand($name, $class) {
    $path      = preg_split('/\s+/', $name);
    $leaf_name = array_pop($path);
    $command   = self::getRootCommand();
    $class     = "Terminus\\Commands\\$class";

    while (!empty($path)) {
      $subcommand_name = $path[0];
      $subcommand      = $command->findSubcommand($path);
      // Create an empty container
      if (!$subcommand) {
        $subcommand = new Dispatcher\CompositeCommand(
          $command,
          $subcommand_name,
          new DocParser('')
        );
        $command->addSubcommand($subcommand_name, $subcommand);
      }
      $command = $subcommand;
    }

    $options = [
      'cache'     => self::getCache(),
      'logger'    => self::getLogger(),
      'outputter' => self::getOutputter(),
      'session'   => Session::instance(),
    ];

    $leaf_command = Dispatcher\CommandFactory::create(
      $leaf_name,
      $class,
      $command,
      $options
    );

    if (!$command->canHaveSubcommands()) {
      throw new TerminusException(
        sprintf(
          "'%s' can't have subcommands.",
          implode(' ', Dispatcher\getPath($command))
        )
      );
    }
    $command->addSubcommand($leaf_name, $leaf_command);
  }

  /**
   * Retrieves and returns the file cache
   *
   * @return FileCache
   */
  public static function getCache() {
    static $cache;

    if (!$cache) {
      $home = getenv('HOME');
      if (!$home) {
        // sometime in windows $HOME is not defined
        $home = getenv('HOMEDRIVE') . '/' . getenv('HOMEPATH');
      }
      $dir = getenv('TERMINUS_CACHE_DIR');
      if (!$dir) {
        $dir = "$home/.terminus/cache";
      }

      // 6 months, 300mb
      $cache = new FileCache($dir, 86400, 314572800);
    }
    $cache->clean();

    return $cache;
  }

  /**
   * Retrieves the config array or a single element from it
   *
   * @param string $key Hash key of element to retrieve from config
   * @return mixed
   */
  public static function getConfig($key = null) {
    $config = self::$options;
    if (isset($config[$key])) {
      $config = $config[$key];
    } elseif (!is_null($key)) {
      self::getLogger()->warning(
        'Unknown config option "{key}".',
        compact('key')
      );
    }
    return $config;
  }

  /**
   * Retrieves the instantiated logger
   *
   * @return Logger $logger
   */
  public static function getLogger() {
    return self::$logger;
  }

  /**
   * Retrieves the instantiated outputter
   *
   * @return OutputterInterface
   */
  public static function getOutputter() {
    return self::$outputter;
  }

  /**
   * Retrieves the root command from the Dispatcher
   *
   * @return \Terminus\Dispatcher\RootCommand
   */
  public static function getRootCommand() {
    static $root;

    if (!$root) {
      $root = new Dispatcher\RootCommand;
    }

    return $root;
  }

  /**
   * Retrieves the runner, creating it if DNE
   *
   * @return \Terminus\Runner
   */
  public static function getRunner() {
    return self::$runner;
  }

  /**
   * Terminus is in test mode
   *
   * @return bool
   */
  public static function isTest() {
    $is_test = (boolean)getenv('CLI_TEST_MODE')
      || (boolean)getenv('VCR_CASSETTE');
    return $is_test;
  }

  /**
   * Launch an external process that takes over I/O.
   *
   * @param string $command       Command to call
   * @param bool   $exit_on_error True to exit if the command returns error
   * @return int   The command exit status
   */
  public static function launch($command, $exit_on_error = true) {
    if (Utils\isWindows()) {
      $command = '"' . $command . '"';
    }
    $r = proc_close(proc_open($command, array(STDIN, STDOUT, STDERR), $pipes));

    if ($r && $exit_on_error) {
      exit($r);
    }

    return $r;
  }

  /**
   * Launch another Terminus command using the runtime arguments for the
   * current process
   *
   * @param string $command       Command to call
   * @param array  $args          Positional arguments to use
   * @param array  $assoc_args    Associative arguments to use
   * @param bool   $exit_on_error True to exit if the command returns error
   * @return int   The command exit status
   */
  public static function launchSelf(
    $command,
    $args = array(),
    $assoc_args = array(),
    $exit_on_error = true
  ) {
    $reused_runtime_args = array(
      'path',
      'url',
      'user',
      'allow-root',
    );

    foreach ($reused_runtime_args as $key) {
      if (array_key_exists($key, self::getRunner()->config)) {
        $assoc_args[$key] = self::getRunner()->config[$key];
      }
    }
    if (Terminus::isTest()) {
      $script_path = __DIR__.'/boot-fs.php';
    } else {
      $script_path = $GLOBALS['argv'][0];
    }

    $php_bin      = '"' . self::getPhpBinary() . '"' ;
    $script_path  = '"' . $script_path . '"';
    $escaped_args = array_map('escapeshellarg', $args);
    $args         = implode(' ', $escaped_args);
    $assoc_args   = Utils\assocArgsToStr($assoc_args);
    $full_command = "$php_bin $script_path $command $args $assoc_args";
    $status       = self::launch($full_command, $exit_on_error);
    return $status;
  }

  /**
   * Set the logger instance to a class property
   *
   * @param array $config Configuration options to send to the logger
   * @return void
   */
  public static function setLogger($config) {
    self::$logger = new Logger(compact('config'));
  }

  /**
   * Set the outputter instance to a class property
   *
   * @param string $format Type of formatter to set on outputter
   * @return void
   */
  public static function setOutputter($format) {
    // Pick an output formatter
    if ($format == 'json') {
      $formatter = new Terminus\Outputters\JSONFormatter();
    } elseif ($format == 'bash') {
      $formatter = new Terminus\Outputters\BashFormatter();
    } else {
      $formatter = new Terminus\Outputters\PrettyFormatter();
    }

    // Create an output service.
    self::$outputter = new Terminus\Outputters\Outputter(
      new Terminus\Outputters\StreamWriter('php://stdout'),
      $formatter
    );
  }

  /**
   * Returns location of PHP with which to run Terminus
   *
   * @return string
   */
  private static function getPhpBinary() {
    if (defined('PHP_BINARY')) {
      $php_bin = PHP_BINARY;
    } elseif (getenv('TERMINUS_PHP_USED')) {
      $php_bin = getenv('TERMINUS_PHP_USED');
    } elseif (getenv('TERMINUS_PHP')) {
      $php_bin = getenv('TERMINUS_PHP');
    } else {
      $php_bin = 'php';
    }
    return $php_bin;
  }

  /**
   * Sets the runner object
   *
   * @param Runner|null $runner Runner object to set
   * @return void
   */
  private function setRunner($runner = null) {
    if (!$runner instanceof Runner) {
      self::$runner = new Runner();
    } else {
      self::$runner = $runner;
    }
  }

}

if (!defined('TERMINUS_ROOT')) {
  define('TERMINUS_ROOT', dirname(__DIR__));
}
require_once TERMINUS_ROOT . '/php/utils.php';
Utils\defineConstants();
Utils\importEnvironmentVariables();
