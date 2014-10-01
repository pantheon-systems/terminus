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

  public function find_command_to_run( $args ) {
    $command = \Terminus::get_root_command();

    $cmd_path = array();


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
    if ( 'auto' === $this->config['colorize'] ) {
      $this->colorize = !\cli\Shell::isPiped();
    } else {
      $this->colorize = $this->config['colorize'];
    }
  }

  private function init_logger() {
    if ( $this->config['silent'] )
      $logger = new \Terminus\Loggers\Quiet;
    else
      $logger = new \Terminus\Loggers\Regular( $this->in_color() );

    Terminus::set_logger( $logger );
  }

  private function init_config() {
    $configurator = \Terminus::get_configurator();

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

  public function run() {
    $this->init_config();
    $this->init_colorization();
    $this->init_logger();

    if ( TRUE === @CLI_TEST_MODE )
      return true;

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

    // First try at showing man page
    if ( 'help' === $this->arguments[0] && ( isset( $this->arguments[1] ) ) ) {
      $this->_run_command();
    }

    # Run the stinkin command!
    $this->_run_command();

  }

}
