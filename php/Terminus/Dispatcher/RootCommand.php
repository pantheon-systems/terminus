<?php

namespace Terminus\Dispatcher;

use Terminus;
use Terminus\Utils;

/**
 * The root node in the command tree.
 */
class RootCommand extends CompositeCommand {

  /**
   * Object constructor, sets object properties
   *
   * @return [RootCommand] $this
   */
  function __construct() {
    $this->parent    = false;
    $this->name      = 'terminus';
    $this->shortdesc = 'Manage Pantheon through the command-line.';
  }

  /**
   * Finds a subcommand of the root command
   *
   * @param [array] $args Arguments to parse into subcommands
   * @return [mixed] $this->$subcommands[$command] or false if DNE
   */
  function findSubcommand(&$args) {
    $command = array_shift($args);
    Utils\loadCommand($command);

    if (!isset($this->subcommands[$command])) {
      return false;
    }

    return $this->subcommands[$command];
  }

  /**
   * Returns long description of this command by parsing the docs
   *
   * @return [string] $binding
   */
  function getLongdesc() {
    $binding = array();

    foreach (Terminus::getConfigurator()->getSpec() as $key => $details) {
      if (($details['runtime'] === false)
        || isset($details['deprecated'])
        || (isset($details['hidden']))
      ) {
        continue;
      } if ($details['runtime']) {
        $synopsis = "--[no-]$key";
      } else {
        $synopsis = "--$key" . $details['runtime'];
      }

      $binding['parameters'][] = array(
        'synopsis' => $synopsis,
        'desc'     => $details['desc']
      );
    }

    if (Terminus::getConfig('format') == 'json') {
      return $binding;
    }
    $binding = Utils\mustacheRender('man-params.mustache', $binding);
    return $binding;
  }

  /**
   * Returns all subcommands of the root command
   *
   * @return [array] $subcommands An array of Subcommand objects
   */
  function getSubcommands() {
    Utils\loadAllCommands();
    $subcommands = parent::getSubcommands();
    return $subcommands;
  }

}
