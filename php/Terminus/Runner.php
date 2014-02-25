<?php

namespace Terminus;

use Terminus;
use Terminus\Utils;
use Terminus\Dispatcher;

class Runner {

  private $global_config_path, $project_config_path;

  private $config, $extra_config;

  private $arguments, $assoc_args;

  private $_early_invoke = array();

  public function __get( $key ) {
    if ( '_' === $key[0] )
      return null;

    return $this->$key;
  }

  private static function get_global_config_path() {
    $config_path = getenv( 'TERMINUS_CONFIG_PATH' );
    if ( isset( $runtime_config['config'] ) ) {
      $config_path = $runtime_config['config'];
    }

    if ( !$config_path ) {
      $config_path = getenv( 'HOME' ) . '/.terminus/config.yml';
    }

    if ( !is_readable( $config_path ) )
      return false;

    return $config_path;
  }

  /**
   * Find the directory that contains the project files. Defaults to the current working dir.
   *
   * @return string An absolute path
   */
  private function find_project_root() {
    if ( !empty( $this->config['path'] ) ) {
      $path = $this->config['path'];
      if ( !Utils\is_path_absolute( $path ) )
        $path = getcwd() . '/' . $path;

      return $path;
    }

    $dir = getcwd();

    // walk the parent directories till we find the git directory at what
    // we assume is the root of the project
    while ( is_readable( $dir ) ) {
      if ( file_exists( "$dir/.git" ) ) {
        return $dir;
      }

      $parent_dir = dirname( $dir );
      if ( empty($parent_dir) || $parent_dir === $dir ) {
        break;
      }
      $dir = $parent_dir;
    }
  }

  private static function set_project_root( $path ) {
    define( 'ABSPATH', rtrim( $path, '/' ) . '/' );

    $_SERVER['DOCUMENT_ROOT'] = realpath( $path );
  }

  public function find_command_to_run( $args ) {
    $command = \Terminus::get_root_command();

    $cmd_path = array();

    $disabled_commands = $this->config['disabled_commands'];

    while ( !empty( $args ) && $command->can_have_subcommands() ) {
      $cmd_path[] = $args[0];
      $full_name = implode( ' ', $cmd_path );

      $subcommand = $command->find_subcommand( $args );

      if ( !$subcommand ) {
        return sprintf(
          "'%s' is not a registered command. See 'terminus help'.",
          $full_name
        );
      }

      if ( in_array( $full_name, $disabled_commands ) ) {
        return sprintf(
          "The '%s' command has been disabled from the config file.",
          $full_name
        );
      }

      $command = $subcommand;
    }

    return array( $command, $args, $cmd_path );
  }

  public function run_command( $args, $assoc_args = array() ) {
    $r = $this->find_command_to_run( $args );
    if ( is_string( $r ) ) {
      Terminus::error( $r );
    }

    list( $command, $final_args, $cmd_path ) = $r;

    $name = implode( ' ', $cmd_path );

    if ( isset( $this->extra_config[ $name ] ) ) {
      $extra_args = $this->extra_config[ $name ];
    } else {
      $extra_args = array();
    }

    try {
      $command->invoke( $final_args, $assoc_args, $extra_args );
    } catch ( Terminus\Iterators\Exception $e ) {
      Terminus::error( $e->getMessage() );
    }
  }

  private function _run_command() {
    $this->run_command( $this->arguments, $this->assoc_args );
  }

  public function in_color() {
    return $this->colorize;
  }

  private function init_colorization() {
    if ( 'auto' === $this->config['color'] ) {
      $this->colorize = !\cli\Shell::isPiped();
    } else {
      $this->colorize = $this->config['color'];
    }
  }

  private function init_logger() {
    if ( $this->config['quiet'] )
      $logger = new \Terminus\Loggers\Quiet;
    else
      $logger = new \Terminus\Loggers\Regular( $this->in_color() );

    Terminus::set_logger( $logger );
  }

  private function wp_exists() {
    return is_readable( ABSPATH . 'wp-includes/version.php' );
  }

  private function check_wp_version() {
    if ( !$this->wp_exists() ) {
      Terminus::error(
        "This does not seem to be a WordPress install.\n" .
        "Pass --path=`path/to/wordpress` or run `wp core download`." );
    }

    include ABSPATH . 'wp-includes/version.php';

    $minimum_version = '3.5.2';

    // @codingStandardsIgnoreStart
    if ( version_compare( $wp_version, $minimum_version, '<' ) ) {
      Terminus::error(
        "Terminus needs WordPress $minimum_version or later to work properly. " .
        "The version currently installed is $wp_version.\n" .
        "Try running `wp core download --force`."
      );
    }
    // @codingStandardsIgnoreEnd
  }

  private function init_config() {
    $configurator = \Terminus::get_configurator();

    // File config
    {
      $this->global_config_path = self::get_global_config_path();

      $configurator->merge_yml( $this->global_config_path );
      $configurator->merge_yml( $this->project_config_path );
    }

    // Runtime config and args
    {
      list( $args, $assoc_args, $runtime_config ) = $configurator->parse_args(
        array_slice( $GLOBALS['argv'], 1 ) );


      $this->arguments = $args;
      $this->assoc_args = $assoc_args;

      $configurator->merge_array( $runtime_config );
    }

    list( $this->config, $this->extra_config ) = $configurator->to_array();
  }

  private function check_root() {
    if ( $this->config['allow-root'] )
      return; # they're aware of the risks!
    if ( !function_exists( 'posix_geteuid') )
      return; # posix functions not available
    if ( posix_geteuid() !== 0 )
      return; # not root

    Terminus::error(
      "YIKES! It looks like you're running this as root. You probably meant to " .
      "run this as the user that your WordPress install exists under.\n" .
      "\n" .
      "If you REALLY mean to run this as root, we won't stop you, but just " .
      "bear in mind that any code on this site will then have full control of " .
      "your server, making it quite DANGEROUS.\n" .
      "\n" .
      "If you'd like to continue as root, please run this again, adding this " .
      "flag:  --allow-root\n" .
      "\n" .
      "If you'd like to run it as the user that this site is under, you can " .
      "run the following to become the respective user:\n" .
      "\n" .
      "    sudo -u USER -i -- wp ...\n" .
      "\n"
    );
  }

  public function run() {
    $this->init_config();
    $this->init_colorization();
    $this->init_logger();

    $this->check_root();

    if ( empty( $this->arguments ) )
      $this->arguments[] = 'help';

    // Load bundled commands early, so that they're forced to use the same
    // APIs as non-bundled commands.
    Utils\load_command( $this->arguments[0] );

    if ( isset( $this->config['require'] ) ) {
      foreach ( $this->config['require'] as $path ) {
        Utils\load_file( $path );
      }
    }

    // Show synopsis if it's a composite command.
    $r = $this->find_command_to_run( $this->arguments );
    if ( is_array( $r ) ) {
      list( $command ) = $r;

      if ( $command->can_have_subcommands() ) {
        $command->show_usage();
        exit;
      }
    }

    // Handle --path parameter
    self::set_project_root( $this->find_project_root() );

    // First try at showing man page
    if ( 'help' === $this->arguments[0] && ( isset( $this->arguments[1] ) || !$this->wp_exists() ) ) {
      $this->_run_command();
    }

    # Run the stinkin command!
    $this->_run_command();

  }

}

