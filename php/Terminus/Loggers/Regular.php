<?php

namespace Terminus\Loggers;

class Regular {
  static $instance;

  function __construct( $in_color ) {
    $this->in_color = $in_color;
    self::$instance = $this;
  }

  protected function write( $handle, $str ) {
    fwrite( $handle, $str );
  }

  private function _line( $message, $label, $color, $handle = STDOUT ) {
    $label = \cli\Colors::colorize( "$color$label:%n", $this->in_color );
    $this->write( $handle, "$label $message\n" );
  }

  function info( $message ) {
    $this->write( STDOUT, $message . "\n" );
  }

  function success( $message ) {
    $this->_line( $message, 'Success', '%G' );
  }

  function warning( $message ) {
    $this->_line( $message, 'Warning', '%C', STDERR );
  }

  function error( $message ) {
    $this->_line( $message, 'Error', '%R', STDERR );
  }

  static function notify( $message ) {
    return new \cli\notify\Dots($message, 5, 0);
  }

  static function redLine($message = " ") {
    $cli = new Self('%1%K');
    $message = \cli\Colors::colorize( "%1%K$message%n", $cli->in_color );
    $cli->write( STDOUT, "$message\n");
  }
}
