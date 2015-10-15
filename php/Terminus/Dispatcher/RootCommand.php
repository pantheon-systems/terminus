<?php

namespace Terminus\Dispatcher;

use Terminus;
use Terminus\Utils;

/**
 * The root node in the command tree.
 */
class RootCommand extends CompositeCommand {

  function __construct() {
    $this->parent = false;
    $this->name = 'terminus';
    $this->shortdesc = 'Manage Pantheon through the command-line.';
  }

  function get_longdesc() {
    $binding = array();

    foreach (Terminus::getConfigurator()->getSpec() as $key => $details) {
      if (
        ($details['runtime'] === false)
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
    return Utils\mustacheRender('man-params.mustache', $binding);
  }

  function find_subcommand(&$args) {
    $command = array_shift($args);

    Utils\loadCommand($command);

    if (!isset($this->subcommands[$command])) {
      return false;
    }

    return $this->subcommands[$command];
  }

  function get_subcommands() {
    Utils\loadAllCommands();

    return parent::get_subcommands();
  }
}

