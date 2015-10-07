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

    foreach (Terminus::get_configurator()->get_spec() as $key => $details) {
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

    if (Terminus::get_config('format') == 'json') {
      return $binding;
    }
    return Utils\mustache_render('man-params.mustache', $binding);
  }

  function find_subcommand(&$args) {
    $command = array_shift($args);

    Utils\load_command($command);

    if (!isset($this->subcommands[$command])) {
      return false;
    }

    return $this->subcommands[$command];
  }

  function get_subcommands() {
    Utils\load_all_commands();

    return parent::get_subcommands();
  }
}

