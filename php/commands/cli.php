<?php

use \Terminus\Dispatcher,
  \Terminus\Utils;

/**
 * Get information about Terminus itself.
 *
 */
class CLI_Command extends Terminus_Command {

  private function command_to_array( $command ) {
    $dump = array(
      'name' => $command->get_name(),
      'description' => $command->get_shortdesc(),
      'longdesc' => $command->get_longdesc(),
    );

    foreach ( $command->get_subcommands() as $subcommand ) {
      $dump['subcommands'][] = self::command_to_array( $subcommand );
    }

    if ( empty( $dump['subcommands'] ) ) {
      $dump['synopsis'] = (string) $command->get_synopsis();
    }

    return $dump;
  }

  /**
   * Print Terminus version.
   */
  function version() {
    Terminus::line('Terminus version: ' . TERMINUS_VERSION . "\nTerminus script: ".TERMINUS_SCRIPT);
  }

  /**
   * Print various data about the CLI environment.
   *
   * ## OPTIONS
   *
   * [--format=<format>]
   * : Accepted values: json
   */
  function info( $_, $assoc_args ) {
    $php_bin = defined( 'PHP_BINARY' ) ? PHP_BINARY : getenv( 'TERMINUS_PHP_USED' );

    $runner = Terminus::get_runner();

    if ( isset( $assoc_args['format'] ) && 'json' === $assoc_args['format'] ) {
      $info = array(
        'php_binary_path' => $php_bin,
        'global_config_path' => $runner->global_config_path,
        'project_config_path' => $runner->project_config_path,
        'wp_cli_dir_path' => TERMINUS_ROOT,
        'wp_cli_version' => TERMINUS_VERSION,
      );

      Terminus::line( json_encode( $info ) );
    } else {
      Terminus::line( "PHP binary:\t" . $php_bin );
      Terminus::line( "PHP version:\t" . PHP_VERSION );
      Terminus::line( "php.ini used:\t" . get_cfg_var( 'cfg_file_path' ) );
      Terminus::line( "Terminus root dir:\t" . TERMINUS_ROOT );
      Terminus::line( "Terminus global config:\t" . $runner->global_config_path );
      Terminus::line( "Terminus project config:\t" . $runner->project_config_path );
      Terminus::line( "Terminus version:\t" . TERMINUS_VERSION );
    }
  }

  /**
   * Dump the list of global parameters, as JSON.
   *
   * @subcommand param-dump
   */
  function param_dump() {
    echo \Terminus\Utils\json_dump( \Terminus::get_configurator()->get_spec() );
  }

  /**
   * Dump the list of installed commands, as JSON.
   *
   * @subcommand cmd-dump
   */
  function cmd_dump() {
    echo \Terminus\Utils\json_dump( self::command_to_array( Terminus::get_root_command() ) );
  }

  /**
   * Generate tab completion strings.
   *
   * ## OPTIONS
   *
   * --line=<line>
   * : The current command line to be executed
   *
   * --point=<point>
   * : The index to the current cursor position relative to the beginning of the command
   */
  function completions( $_, $assoc_args ) {
    $line = substr( $assoc_args['line'], 0, $assoc_args['point'] );
    $compl = new \Terminus\Completions( $line );
    $compl->render();
  }

  /**
  * Clear session data
  * @subcommand session-clear
  */
  function session_clear() {
    $this->cache->remove("session");
  }

  /**
  * @subcommand session-dump
  */
  public function session_dump() {
   $session = $this->cache->get_data("session");
   echo \Terminus\Utils\json_dump( $session );
  }

  /**
  * @subcommand cache-clear
  */
  public function cache_clear($cache = null) {
    $this->cache->flush($cache,'session');
  }

}

Terminus::add_command( 'cli', 'CLI_Command' );
