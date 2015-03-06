<?php

use \Terminus\Utils;
use \Terminus\Dispatcher;
use \Terminus\FileCache;
use \Terminus\Request;

/**
 * Various utilities for Terminus commands.
 */
class Terminus {

  private static $configurator;

  private static $logger;

  private static $hooks = array(), $hooks_passed = array();

  /**
   * Set the logger instance.
   *
   * @param object $logger
   */
  static function set_logger( $logger ) {
    self::$logger = $logger;
  }

  static function get_configurator() {
    static $configurator;

    if ( !$configurator ) {
      $configurator = new Terminus\Configurator( TERMINUS_ROOT . '/php/config-spec.php' );
    }

    return $configurator;
  }

  static function get_root_command() {
    static $root;

    if ( !$root ) {
      $root = new Dispatcher\RootCommand;
    }

    return $root;
  }

  static function get_runner() {
    // the entire process needs an exception wrapper
    try {
      static $runner;

      if ( !$runner ) {
        $runner = new Terminus\Runner;
      }

      return $runner;
    } catch(\Exception $e) {
      Terminus::error($e->getMessage());
    }
  }

  /**
   * @return FileCache
   */
  public static function get_cache() {
    static $cache;

    if ( !$cache ) {
      $home = getenv( 'HOME' );
      if ( !$home ) {
        // sometime in windows $HOME is not defined
        $home = getenv( 'HOMEDRIVE' ) . '/' . getenv( 'HOMEPATH' );
      }
      $dir = getenv( 'TERMINUS_CACHE_DIR' ) ? : "$home/.terminus/cache";

      // 6 months, 300mb
      $cache = new FileCache( $dir, 86400, 314572800 );

      // clean older files on shutdown with 1/50 probability
      if ( 0 === mt_rand( 0, 50 ) ) {
        register_shutdown_function( function () use ( $cache ) {
          $cache->clean();
        } );
      }
    }

    return $cache;
  }

  static function colorize( $string ) {
    return \cli\Colors::colorize( $string, self::get_runner()->in_color() );
  }

  /**
   * Add a command to the terminus list of commands
   *
   * @param string $name The name of the command that will be used in the CLI
   * @param string $class The command implementation
   * @param array $args An associative array with additional parameters:
   *   'before_invoke' => callback to execute before invoking the command
   */
  static function add_command( $name, $class, $args = array() ) {

    $path = preg_split( '/\s+/', $name );

    $leaf_name = array_pop( $path );
    $full_path = $path;

    $command = self::get_root_command();

    while ( !empty( $path ) ) {
      $subcommand_name = $path[0];
      $subcommand = $command->find_subcommand( $path );
      // create an empty container
      if ( !$subcommand ) {
        $subcommand = new Dispatcher\CompositeCommand( $command, $subcommand_name,
          new \Terminus\DocParser( '' ) );
        $command->add_subcommand( $subcommand_name, $subcommand );
      }

      $command = $subcommand;
    }

    $leaf_command = Dispatcher\CommandFactory::create( $leaf_name, $class, $command );

    if ( ! $command->can_have_subcommands() ) {
      throw new Exception( sprintf( "'%s' can't have subcommands.",
        implode( ' ' , Dispatcher\get_path( $command ) ) ) );
    }
    $command->add_subcommand( $leaf_name, $leaf_command );
  }

  /**
   * Prompt the user for input
   *
   * @param string $message
   */
  static function prompt( $message = '', $params = array(), $default=null ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    $response = \cli\prompt( $message );
    if(empty($response) AND $default) {
      $response = $default;
    }
    return $response;
  }

  /**
   * Display a message in the CLI and end with a newline
   *
   * @param string $message
   */
  static function line( $message = '', $params = array() ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    echo \cli\line($message);
  }

  /**
   * Log an informational message.
   *
   * @param string $message
   */
  static function log( $message, $params = array() ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    self::$logger->info( $message );
  }

  /**
   * Colorized logging
   * 
   * @param $message string
   * @param $params array
  **/
  static function color($message) {
    \Terminus\Loggers\Regular::coloredOutput($message);
  }

  /**
   * Display a success in the CLI and end with a newline
   *
   * @param string $message
   */
  static function success( $message, $params = array() ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    self::$logger->success( $message );
  }

  /**
   * Display a warning in the CLI and end with a newline
   *
   * @param string $message
   */
  static function warning( $message, $params = array() ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    if (!Terminus::$logger) {
      Terminus::set_logger(new \Terminus\Loggers\Regular(TRUE));
    }
    Terminus::$logger->warning( self::error_to_string( $message ) );
  }

  /**
   * Display an error in the CLI and end with a newline
   *
   * @param string $message
   */
  static function error( $message, $params = array() ) {
    if ( !empty($params) ) {
      $message = vsprintf($message, $params);
    }
    if ( ! isset( self::get_runner()->assoc_args[ 'completions' ] ) ) {
      self::$logger->error( self::error_to_string( $message ) );
    }

    exit(1);
  }

  /**
   * Ask for confirmation before running a destructive operation.
   */
  static function confirm( $question, $assoc_args = array(), $params = array()) {
      if (\Terminus::get_config('yes')) return true;
      $question = vsprintf($question, $params);
      fwrite( STDOUT, $question . " [y/n] " );

      $answer = trim( fgets( STDIN ) );

      if ( 'y' != $answer )
        exit;
      return true;
  }

  /**
   * Read value from a positional argument or from STDIN.
   *
   * @param array $args The list of positional arguments.
   * @param int $index At which position to check for the value.
   *
   * @return string
   */
  public static function get_value_from_arg_or_stdin( $args, $index ) {
    if ( isset( $args[ $index ] ) ) {
      $raw_value = $args[ $index ];
    } else {
      // We don't use file_get_contents() here because it doesn't handle
      // Ctrl-D properly, when typing in the value interactively.
      $raw_value = '';
      while ( ( $line = fgets( STDIN ) ) !== false ) {
        $raw_value .= $line;
      }
    }

    return $raw_value;
  }

  /**
   * Read a value, from various formats.
   *
   * @param mixed $value
   * @param array $assoc_args
   */
  static function read_value( $raw_value, $assoc_args = array() ) {
    if ( isset( $assoc_args['format'] ) && 'json' == $assoc_args['format'] ) {
      $value = json_decode( $raw_value, true );
      if ( null === $value ) {
        Terminus::error( sprintf( 'Invalid JSON: %s', $raw_value ) );
      }
    } else {
      $value = $raw_value;
    }

    return $value;
  }

  /**
   * Display a value, in various formats
   *
   * @param mixed $value
   * @param array $assoc_args
   */
  static function print_value( $value, $assoc_args = array() ) {
    if ( isset( $assoc_args['format'] ) && 'json' == $assoc_args['format'] ) {
      $value = json_encode( $value );
    } elseif ( is_array( $value ) || is_object( $value ) ) {
      $value = var_export( $value );
    }

    echo $value . "\n";
  }

  /**
   * Convert a error into a string
   *
   * @param mixed $errors
   * @return string
   */
  static function error_to_string( $errors ) {
    if ( is_string( $errors ) ) {
      return $errors;
    }

    if ( is_object( $errors ) && is_a( $errors, 'WP_Error' ) ) {
      foreach ( $errors->get_error_messages() as $message ) {
        if ( $errors->get_error_data() )
          return $message . ' ' . $errors->get_error_data();
        else
          return $message;
      }
    }
  }

  /**
   * Launch an external process that takes over I/O.
   *
   * @param string Command to call
   * @param bool Whether to exit if the command returns an error status
   *
   * @return int The command exit status
   */
  static function launch( $command, $exit_on_error = true ) {
    $r = proc_close( proc_open( $command, array( STDIN, STDOUT, STDERR ), $pipes ) );

    if ( $r && $exit_on_error )
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
  static function launch_self( $command, $args = array(), $assoc_args = array(), $exit_on_error = true ) {
    $reused_runtime_args = array(
      'path',
      'url',
      'user',
      'allow-root',
    );

    foreach ( $reused_runtime_args as $key ) {
      if ( array_key_exists( $key, self::get_runner()->config ) )
        $assoc_args[ $key ] = self::get_runner()->config[$key];
    }

    $php_bin = self::get_php_binary();

    if (Terminus::is_test()) {
      $script_path = __DIR__.'/boot-fs.php';
    } else {
      $script_path = $GLOBALS['argv'][0];
    }

    $args = implode( ' ', array_map( 'escapeshellarg', $args ) );
    $assoc_args = \Terminus\Utils\assoc_args_to_str( $assoc_args );

    $full_command = "{$php_bin} {$script_path} {$command} {$args} {$assoc_args}";

    return self::launch( $full_command, $exit_on_error );
  }

  private static function get_php_binary() {
    if ( defined( 'PHP_BINARY' ) )
      return PHP_BINARY;

    if ( getenv( 'TERMINUS_PHP_USED' ) )
      return getenv( 'TERMINUS_PHP_USED' );

    if ( getenv( 'TERMINUS_PHP' ) )
      return getenv( 'TERMINUS_PHP' );

    return 'php';
  }

  static function get_config( $key = null ) {
    if ( null === $key ) {
      return self::get_runner()->config;
    }

    if ( !isset( self::get_runner()->config[ $key ] ) ) {
      //self::warning( "Unknown config option '$key'." );
      return null;
    }

    return self::get_runner()->config[ $key ];
  }

  static function set_config($key, $value) {
    self::get_runner()->config[ $key ] = $value;
    return self::get_runner()->config;
  }

  /**
   * @deprecated
   */
  static function menu( $data, $default = null, $text = "Select one", $return_value=false ) {
    echo PHP_EOL;
    $index = \cli\Streams::menu($data,$default,$text);
    if ($return_value) {
      return $data[$index];
    }
    return $index;
  }

  /**
   * Run a given command.
   *
   * @param array
   * @param array
   */
  static function run_command( $args, $assoc_args = array() ) {
    self::get_runner()->run_command( $args, $assoc_args );
  }

  /**
   * Terminus is in test mode
   */
  static function is_test() {
    if (defined('CLI_TEST_MODE') AND false !== CLI_TEST_MODE)
      return true;
    if (getenv("CLI_TEST_MODE"))
      return true;
    return false;
  }

  /**
   * Latest release
  */
  static function version_available() {
    $cache = Terminus::get_cache();
    if (! $release = $cache->get_data(__FUNCTION__) ) {
      $url = "https://api.github.com/repos/pantheon-systems/cli/releases?per_page=1";
      $response = Request::send($url,'GET');   
      $json = $response->getBody(TRUE);
      $release = array_shift(json_decode($json));
      $cache->put_data(__FUNCTION__,$release);
    }
    return $release->tag_name;
  }

  static public function update_available() {
    $latest = Terminus::version_available();
    if (version_compare($latest, TERMINUS_VERSION, ">")) {
      return true;
    }
    return false; 
  }
}
