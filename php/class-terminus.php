<?php

use Terminus\Configurator;
use Terminus\Dispatcher;
use Terminus\FileCache;
use Terminus\Internationalizer as I18n;
use Terminus\Runner;
use Terminus\Utils;

/**
 * Various utilities for Terminus commands.
 */
class Terminus {
  private static $configurator;
  private static $hooks = array();
  private static $hooks_passed = array();
  private static $inputter;
  private static $logger;
  private static $outputter;

  static function get_configurator() {
    static $configurator;

    if(!$configurator) {
      $configurator = new Configurator(TERMINUS_ROOT . '/php/config-spec.php');
    }

    return $configurator;
  }

  static function get_root_command() {
    static $root;

    if(!$root) {
      $root = new Dispatcher\RootCommand;
    }

    return $root;
  }

  static function get_runner() {
    // the entire process needs an exception wrapper
    try {
      static $runner;

      if(!isset($runner) || !$runner) {
        $runner = new Runner();
      }

      return $runner;
    } catch(\Exception $e) {
      Terminus::log('error', $e->getMessage());
      exit(1);
    }
  }

  /**
   * @return FileCache
   */
  public static function get_cache() {
    static $cache;

    if(!$cache) {
      $home = getenv('HOME');
      if(!$home) {
        // sometime in windows $HOME is not defined
        $home = getenv('HOMEDRIVE') . '/' . getenv('HOMEPATH');
      }
      $dir = getenv('TERMINUS_CACHE_DIR') ? : "$home/.terminus/cache";

      // 6 months, 300mb
      $cache = new FileCache($dir, 86400, 314572800);
    }
    $cache->clean();

    return $cache;
  }

  /**
   * Add a command to the terminus list of commands
   *
   * @param string $name The name of the command that will be used in the CLI
   * @param string $class The command implementation
   * @param array $args An associative array with additional parameters:
   *   'before_invoke' => callback to execute before invoking the command
   */
  static function add_command($name, $class, $args = array()) {

    $path = preg_split('/\s+/', $name);

    $leaf_name = array_pop($path);
    $full_path = $path;

    $command = self::get_root_command();

    while (!empty($path)) {
      $subcommand_name = $path[0];
      $subcommand = $command->find_subcommand($path);
      // create an empty container
      if(!$subcommand) {
        $subcommand = new Dispatcher\CompositeCommand($command, $subcommand_name,
          new DocParser(''));
        $command->add_subcommand($subcommand_name, $subcommand);
      }

      $command = $subcommand;
    }

    $leaf_command = Dispatcher\CommandFactory::create($leaf_name, $class, $command);

    if(! $command->can_have_subcommands()) {
      throw new Exception(sprintf("'%s' can't have subcommands.",
        implode(' ' , Dispatcher\get_path($command))));
    }
    $command->add_subcommand($leaf_name, $leaf_command);
  }


  /**
   * Output a colorized string to STDOUT
   * TODO: Clean this up. There should be no direct access to STDOUT/STDERR
   *
   * @param $string
   * @return string
   */
  static function colorize($string) {
    return \cli\Colors::colorize($string, self::get_runner()->in_color());
  }

  /**
   * Display a message in the CLI and end with a newline
   * TODO: Clean this up. There should be no direct access to STDOUT/STDERR
   *
   * @param string $message
   */
  static function line($message = '') {
    fwrite(STDERR, $message . PHP_EOL);
  }

  /**
   * Display a message in the CLI and end with no newline
   * TODO: Clean this up. There should be no direct access to STDOUT/STDERR
   *
   * @param string $message
   */
  static function out($message = '') {
    fwrite(STDERR, $message);
  }

  /**
   * Prompt the user for input
   *
   * @param string $message
   */
  static function prompt($message = '', $params = array(), $default=null) {
    if(!empty($params)) {
      $message = vsprintf($message, $params);
    }
    $response = \cli\prompt($message);
    if(empty($response) AND $default) {
      $response = $default;
    }
    return $response;
  }

  /**
   * Prompt the user for input
   *
   * @param string $message
   */
  static function promptSecret($message = '', $params = array(), $default=null) {
    exec("stty -echo");
    $response = Terminus::prompt($message, $params);
    exec("stty echo");
    Terminus::line();
    return $response;
  }


  /**
   * @deprecated
   */
  static function menu($data, $default = null, $text = "Select one", $return_value=false) {
    echo PHP_EOL;
    $index = \cli\Streams::menu($data,$default,$text);
    if($return_value) {
      return $data[$index];
    }
    return $index;
  }

  /**
   * Ask for confirmation before running a destructive operation.
   */
  //TODO: Move this functionality to the inputter/input helper
  static function confirm($question, $assoc_args = array(), $params = array()) {
      $i18n = new I18n();
      if(\Terminus::get_config('yes')) return true;
      $question = $i18n->get($question, $params);
      fwrite(STDOUT, $question . " [y/n] ");

      $answer = trim(fgets(STDIN));

      if('y' != $answer)
        exit;
      return true;
  }

  /**
   * Launch an external process that takes over I/O.
   *
   * @param string Command to call
   * @param bool Whether to exit if the command returns an error status
   *
   * @return int The command exit status
   */
  static function launch($command, $exit_on_error = true) {
    $r = proc_close(proc_open($command, array(STDIN, STDOUT, STDERR), $pipes));

    if($r && $exit_on_error)
      exit($r);

    return $r;
  }

  /**
   * Launch another Terminus command using the runtime arguments for the current process
   *
   * @param string Command to call
   * @param array $args Positional arguments to use
   * @param array $assoc_args Associative arguments to use
   * @param bool Whether to exit if the command returns an error status
   *
   * @return int The command exit status
   */
  static function launch_self($command, $args = array(), $assoc_args = array(), $exit_on_error = true) {
    $reused_runtime_args = array(
      'path',
      'url',
      'user',
      'allow-root',
   );

    foreach($reused_runtime_args as $key) {
      if(array_key_exists($key, self::get_runner()->config))
        $assoc_args[ $key ] = self::get_runner()->config[$key];
    }

    $php_bin = self::get_php_binary();

    if(Terminus::is_test()) {
      $script_path = __DIR__.'/boot-fs.php';
    } else {
      $script_path = $GLOBALS['argv'][0];
    }

    $args = implode(' ', array_map('escapeshellarg', $args));
    $assoc_args = \Terminus\Utils\assoc_args_to_str($assoc_args);

    $full_command = "{$php_bin} {$script_path} {$command} {$args} {$assoc_args}";

    return self::launch($full_command, $exit_on_error);
  }

  private static function get_php_binary() {
    if(defined('PHP_BINARY'))
      return PHP_BINARY;

    if(getenv('TERMINUS_PHP_USED'))
      return getenv('TERMINUS_PHP_USED');

    if(getenv('TERMINUS_PHP'))
      return getenv('TERMINUS_PHP');

    return 'php';
  }

  static function get_config($key = null) {
    if(null === $key) {
      return self::get_runner()->config;
    }

    if(!isset(self::get_runner()->config[ $key ])) {
      self::get_logger()->warning("Unknown config option '{key}'.", array('key' => $key));
      return null;
    }

    return self::get_runner()->config[ $key ];
  }

  static function set_config($key, $value) {
    self::get_runner()->config[ $key ] = $value;
    return self::get_runner()->config;
  }


  /**
   * Run a given command.
   *
   * @param array
   * @param array
   */
  static function run_command($args, $assoc_args = array()) {
    self::get_runner()->run_command($args, $assoc_args);
  }

  /**
   * Terminus is in test mode
   */
  static function is_test() {
    if(defined('CLI_TEST_MODE') && (CLI_TEST_MODE !== false)) {
      return true;
    }
    $is_test = (boolean)getenv("CLI_TEST_MODE");
    return $is_test;
  }

  /**
   * Set the inputter instance.
   *
   * @param [object] $inputter
   * @return [void]
   */
  static function set_inputter($inputter) {
    self::$inputter = $inputter;
  }

  /**
   * Set the outputter instance.
   *
   * @param LoggerInterface $logger
   */
  static function set_logger($logger) {
    self::$logger = $logger;
  }

  /**
   * Retrieves the instantiated inputter
   *
   * @return [Inputter] $inputter
   */
  static function get_inputter() {
    return self::$inputter;
  }

  /**
   * Retrieves the instantiated logger
   *
   * @return LoggerInterface $logger
   */
  static function get_logger() {
    return self::$logger;
  }

  /**
   * Set the outputter instance.
   *
   * @param OutputterInterface $outputter
   */
  static function set_outputter($outputter) {
    self::$outputter = $outputter;
  }

  /**
   * Retrieves the instantiated outputter
   *
   * @return OutputterInterface $outputter
   */
  static function get_outputter() {
    return self::$outputter;
  }

}
