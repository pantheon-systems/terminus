<?php

namespace Terminus\Fetchers;

abstract class Base {

  /**
   * @param string $msg The message to display when an item is not found
   */
  protected $msg;

  /**
   * @param string $arg The raw CLI argument
   * @return mixed|false The item if found; false otherwise
   */
  abstract public function get( $arg );

  /**
   * Like get(), but calls Terminus::error() instead of returning false.
   *
   * @param string $arg The raw CLI argument
   */
  public function get_check( $arg ) {
    $item = $this->get( $arg );

    if ( ! $item ) {
      \Terminus::error( sprintf( $this->msg, $arg ) );
    }

    return $item;
  }

  /**
   * @param array The raw CLI arguments
   * @return array The list of found items
   */
  public function get_many( $args ) {
    $items = array();

    foreach ( $args as $arg ) {
      $item = $this->get( $arg );

      if ( $item ) {
        $items[] = $item;
      } else {
        \Terminus::warning( sprintf( $this->msg, $arg ) );
      }
    }

    return $items;
  }
}

