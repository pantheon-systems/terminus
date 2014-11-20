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

  static function greenLine($message = " ") {
    $cli = new Self('%2%K');
    $message = \cli\Colors::colorize( "%2%K$message%n", $cli->in_color );
    $cli->write( STDOUT, "$message\n");
  }

  static function coloredOutput($message = "", $print = true) {
    $cli = new Self('');
    // we're not using regex here because simple str_replace is faster. However,
    // we may need to go that route if this function gets too complex
    $message = str_replace('</M>','%n', str_replace('<M>','%M',$message) );
    $message = str_replace('</m>','%n', str_replace('<m>','%m',$message) );
    $message = str_replace('</G>','%n', str_replace('<G>','%G',$message) );
    $message = str_replace('</y>','%n', str_replace('<y>','%y',$message) );
    $message = str_replace('</Y>','%n', str_replace('<Y>','%Y',$message) );
    $message = str_replace('</R>','%n', str_replace('<R>','%R',$message) );
    $message = str_replace('</r>','%n', str_replace('<r>','%r',$message) );
    $message = \cli\Colors::colorize( "$message", $cli->in_color );
    if ($print)
      $cli->write( STDOUT, "$message\n");
    return $message;
  }
}
