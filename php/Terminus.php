<?php

use Terminus\Dispatcher;
use Terminus\Dispatcher\CompositeCommand;
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
   * Retrieves and returns the users local configuration directory (~/terminus)
   *
   * @return string
   */
  public static function getUserConfigDir() {
    $terminus_config_dir = getenv('TERMINUS_CONFIG_DIR');

    if (!$terminus_config_dir) {
      $home = getenv('HOME');
      if (!$home) {
        // sometime in windows $HOME is not defined
        $home = getenv('HOMEDRIVE') . '/' . getenv('HOMEPATH');
      }
      if ($home) {
        $terminus_config_dir = getenv('HOME') . '/terminus';
      }
    }
    return $terminus_config_dir;
  }

  /**
   * Retrieves and returns the local config directory
   *
   * @return string
   */
  public static function getUserPluginsDir() {
    if ($config = self::getUserConfigDir()) {
      $plugins_dir = "$config/plugins";
      if (file_exists($plugins_dir)) {
        return $plugins_dir;
      }
    }
    return false;
  }

  /**
   * Retrieves and returns a list of plugin's base directories
   *
   * @return array
   */
  public static function getUserPlugins() {
    $out = array();
    if ($plugins_dir = self::getUserPluginsDir()) {
      $plugin_iterator = new \DirectoryIterator($plugins_dir);
      foreach ($plugin_iterator as $dir) {
        if (!$dir->isDot()) {
          $out[] = $dir->getPathname();
        }
      }
    }
    return $out;
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
      self::loadAllCommands($root);
    }

    return $root;
  }

  /**
   * Includes every command file in the commands directory
   *
   * @param CompositeCommand $parent The parent command to add the new commands to
   *
   * @return void
   */
  private static function loadAllCommands(CompositeCommand $parent) {
    // Create a list of directories where commands might live.
    $directories = array();

    // Add the directory of core commands first.
    $directories[] = TERMINUS_ROOT . '/php/Terminus/Commands';

    // Find the command directories from the third party plugins directory.
    foreach (self::getUserPlugins() as $dir) {
      $directories[] = "$dir/Commands/";
    }

    // Include all class files in the command directories.
    foreach ($directories as $cmd_dir) {
      if ($cmd_dir && file_exists($cmd_dir)) {
        $iterator = new \DirectoryIterator($cmd_dir);
        foreach ($iterator as $file) {
          if ($file->isFile() && $file->isReadable() && $file->getExtension() == 'php') {
            include_once $file->getPathname();
          }
        }
      }
    }

    // Find the defined command classes and add them to the given base command.
    $classes = get_declared_classes();
    $options = [
      'cache'     => self::getCache(),
      'logger'    => self::getLogger(),
      'outputter' => self::getOutputter(),
      'session'   => Session::instance(),
    ];

    foreach ($classes as $class) {
      $reflection = new \ReflectionClass($class);
      if ($reflection->isSubclassOf('Terminus\Commands\TerminusCommand')) {
        Dispatcher\CommandFactory::create(
          $reflection->getName(),
          $parent,
          $options
        );
      }
    }
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
