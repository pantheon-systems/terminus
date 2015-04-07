<?php
use \Terminus\Products;

/**
 * Show Pantheon product information
 *
 */
class Products_Command extends Terminus_Command {

  /**
  * Search for Pantheon product info
  *
  * ### OPTIONS
  *
  * [--category=<category>]
  * : general, publishing, commerce, etc
  *
  * [--type=<type>]
  * : Pantheon internal product type definition
  *
  * [--framework=<drupal|wordpress>]
  * : Filter based on framework
  *
  * @subcommand list
  * @alias all
  *
  **/
  public function all( $args = array(), $assoc_args = array()) {

    $defaults = array(
      'type' => '',
      'category' => '',
      'framework' => '',
    );

    $assoc_args = array_merge( $defaults, $assoc_args );
    $products = Products::instance();
    $this->handleDisplay($products->query($assoc_args),$assoc_args);
    return $products;
  }


}
Terminus::add_command( 'products', 'Products_Command' );
