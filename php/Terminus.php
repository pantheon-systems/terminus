<?php

use Terminus\Dispatcher;
use Terminus\Dispatcher\CompositeCommand;
use Terminus\FileCache;
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
   * @var FileCache
   */
  private static $cache;
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
   * @var RootCommand
   */
  private static $root_command;

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
      'output'   => 'php://stdout',
    );
    self::$options = $options = array_merge($default_options, $arg_options);

    $this->setCache();
    $this->setLogger($options);
    $this->setOutputter($options['format'], $options['output']);
  }

  /**
   * Retrieves and returns the file cache
   *
   * @return FileCache
   */
  public static function getCache() {
    return self::$cache;
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
      throw new TerminusException(
        'Unknown config option "{key}".',
        compact('key'),
        1
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
    if (!isset(self::$root_command)) {
      self::setRootCommand();
    }
    return self::$root_command;
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
   * Sets a file cache instance to a class property
   *
   * @return void
   */
  public static function setCache() {
    $home = getenv('HOME');

    if (!$home) {
      //Sometimes in Windows, $HOME is not defined.
      $home = getenv('HOMEDRIVE') . '/' . getenv('HOMEPATH');
    }
    $dir = getenv('TERMINUS_CACHE_DIR');
    if (!$dir) {
      $dir = "$home/.terminus/cache";
    }

    // 6 months, 300mb
    $cache = new FileCache($dir, 86400, 314572800);
    $cache->clean();
    self::$cache = $cache;
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
   * @param string $format      Type of formatter to set on outputter
   * @param string $destination Where output will be written to
   * @return void
   */
  public static function setOutputter($format, $destination) {
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
      new Terminus\Outputters\StreamWriter($destination),
      $formatter
    );
  }

  /**
   * Retrieves and returns a list of plugin's base directories
   *
   * @return array
   */
  private static function getUserPlugins() {
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
   * Retrieves and returns the local config directory
   *
   * @return string
   */
  private static function getUserPluginsDir() {
    if ($config = self::getUserConfigDir()) {
      $plugins_dir = "$config/plugins";
      if (file_exists($plugins_dir)) {
        return $plugins_dir;
      }
    }
    return false;
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
   * Set the root command instance to a class property
   *
   * @return void
   */
  private static function setRootCommand() {
    self::$root_command = new Dispatcher\RootCommand();
    self::loadAllCommands(self::$root_command);
  }

}

if (!defined('TERMINUS_ROOT')) {
  define('TERMINUS_ROOT', dirname(__DIR__));
}

require_once TERMINUS_ROOT . '/php/utils.php';
Utils\defineConstants();
Utils\importEnvironmentVariables();
