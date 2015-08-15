<?php

use \Terminus\Upstreams;

/**
 * Show Pantheon upstream information
 */
class Upstreams_Command extends TerminusCommand {

  /**
  * Search for Pantheon upstream info
  *
  * ### OPTIONS
  *
  * [--category=<category>]
  * : general, publishing, commerce, etc
  *
  * [--type=<type>]
  * : Pantheon internal upstream type definition
  *
  * [--framework=<drupal|wordpress>]
  * : Filter based on framework
  *
  * @subcommand list
  * @alias all
  **/
  public function all($args = array(), $assoc_args = array()) {
    $defaults   = array(
      'type'      => '',
      'category'  => '',
      'framework' => '',
    );
    $assoc_args = array_merge($defaults, $assoc_args);
    $upstreams  = Upstreams::instance();
    $this->handleDisplay($upstreams->query($assoc_args), $assoc_args);
    return $upstreams;
  }

}

Terminus::add_command('upstreams', 'Upstreams_Command');
