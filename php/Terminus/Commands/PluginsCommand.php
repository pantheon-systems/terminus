<?php

namespace Terminus\Commands;

use Terminus\Collections\Plugins;

/**
 * Manage Terminus plugins
 *
 * @command plugins
 */
class PluginsCommand extends TerminusCommand {
  /**
   * @var Plugins
   */
  private $plugins;

  /**
   * Instantiates object
   *
   * @param array $options Options to construct the command object
   * @returns PluginsCommand
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->plugins = new Plugins();
  }

  /**
   * Show a list of your instruments on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $plugins = array_map(
      function($plugin) {
        $data = $plugin->serialize();
        return $data;
      },
      $this->plugins->all()
    );
    if (empty($plugins)) {
      $this->log()->info('No plugins have been found.');
    }
    $this->output()->outputRecordList($plugins);
  }

  /**
   * Installs a plugin to the local system
   *
   * ## OPTIONS
   * [--plugin=<plugin>]
   * : Slug of the plugin to be installed
   *
   * @subcommand install
   * @alias add
   */
  public function install($args, $assoc_args) {
    $plugin = $this->input()->plugin(
      [
        'args' => $assoc_args,
        'filter' => function($plugin) {
          return !$plugin->isInstalled();
        },
      ]
    );
    if ($plugin->install() == 0) {
      $this->log()->info(
        '{plugin} has been installed.',
        ['plugin' => $plugin->get('name'),]
      );
    }
  }

  /**
   * Uninstalls a plugin from the local system
   *
   * ## OPTIONS
   * [--plugin=<plugin>]
   * : Slug of the plugin to be uninstalled
   *
   * @subcommand uninstall
   * @alias remove
   */
  public function uninstall($args, $assoc_args) {
    $plugin = $this->input()->plugin(
      [
        'args' => $assoc_args,
        'filter' => function($plugin) {
          return $plugin->isInstalled();
        },
      ]
    );
    $this->input()->confirm(
      [
        'message' => 'Are you sure you want to uninstall %s?',
        'context' => $plugin->get('name'),
        'args'    => $assoc_args,
      ]
    );
    if ($plugin->uninstall() == 0) {
      $this->log()->info(
        '{plugin} has been uninstalled.',
        ['plugin' => $plugin->get('name'),]
      );
    }
  }

}

