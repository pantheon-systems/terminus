<?php

namespace Terminus\Dispatcher;

use Terminus\Configurator;
use Terminus\Utils;

/**
 * The root node in the command tree.
 */
class RootCommand extends CompositeCommand {

  /**
   * Object constructor, sets object properties
   */
  function __construct() {
    $this->parent    = false;
    $this->name      = 'terminus';
    $this->shortdesc = 'Manage Pantheon through the command-line.';
  }

  /**
   * Finds a subcommand of the root command
   *
   * @param array $args Arguments to parse into subcommands
   * @return Subcommand|false
   */
  function findSubcommand(&$args) {
    $command = array_shift($args);

    if (!isset($this->subcommands[$command])) {
      return false;
    }

    return $this->subcommands[$command];
  }

  /**
   * Returns long description of this command by parsing the docs
   *
   * @return array
   */
  function getLongdesc() {
    $binding      = [];
    $configurator = new Configurator();
    $spec         = $configurator->getSpec();

    foreach ($spec as $key => $details) {
      if (($details['runtime'] === false)
        || isset($details['deprecated'])
        || (isset($details['hidden']))
      ) {
        continue;
      } else {
        $synopsis = "--$key" . $details['runtime'];
      }

      $binding['parameters'][] = array(
        'synopsis' => $synopsis,
        'desc'     => $details['desc']
      );
    }

    return $binding;
  }

  /**
   * Returns all subcommands of the root command
   *
   * @return Subcommand[]
   */
  function getSubcommands() {
    $subcommands = parent::getSubcommands();
    return $subcommands;
  }

}
