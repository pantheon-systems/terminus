<?php

namespace Terminus\Commands;

use Terminus\Completions;
use Terminus\Configurator;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\Collections\Sites;
use Terminus\Models\User;
use Terminus\Session;

/**
 * Get information about Terminus itself.
 *
 * @command cli
 */
class CliCommand extends TerminusCommand {

  /**
   * Object constructor
   *
   * @param array $options Options to construct the command object
   * @return CliCommand
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Clear cached data
   *
   * ## OPTIONS
   *
   * [--cache=<cache>]
   * : specific cache key to clear
   *
   * @subcommand cache-clear
   */
  public function cacheClear($args, $assoc_args) {
    if (isset($assoc_args['cache'])) {
      $this->cache->remove($assoc_args['cache']);
    } else {
      $this->cache->flush();
    }
  }

  /**
   * Dump the list of installed commands, as JSON.
   *
   * @subcommand cmd-dump
   */
  public function cmdDump() {
    $this->output()->outputDump(
      $this->commandToArray($this->runner->getRootCommand())
    );
  }

  /**
   * Generate tab completion strings.
   *
   * ## OPTIONS
   *
   * --line=<line>
   * : The current command line to be executed
   *
   * [--point=<point>]
   * : The index to the current cursor position relative to the beginning of
   *   the command
   */
  public function completions($args, $assoc_args) {
    $line = $assoc_args['line'];
    if (isset($assoc_args['point'])) {
      $line = substr($line, 0, $assoc_args['point']);
    }
    $completions = new Completions($line);
    $options     = $completions->getOptions();
    foreach ($options as $option) {
      $this->output()->line($option);
    }
  }

  /**
   * Instantiate a console within Terminus
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of site to load
   *
   * @subcommand console
   */
  public function console($args, $assoc_args) {
    $user = Session::getUser();
    if (isset($assoc_args['site'])) {
      $site = $this->sites->get(
        $this->input()->siteName(array('args' => $assoc_args))
      );
    }

    eval(\Psy\sh());
  }

  /**
   * Print various data about the CLI environment.
   *
   * ## OPTIONS
   *
   * [--format=<format>]
   * : Accepted values: json
   */
  public function info($args, $assoc_args) {
    $info   = array(
      'php_binary_path'     => TERMINUS_PHP,
      'php_version'         => PHP_VERSION,
      'php_ini'             => get_cfg_var('cfg_file_path'),
      'project_config_path' => $this->runner->getUserConfigDir(),
      'wp_cli_dir_path'     => TERMINUS_ROOT,
      'wp_cli_version'      => TERMINUS_VERSION,
    );
    $labels = array(
      'php_binary_path'     => 'PHP binary',
      'php_version'         => 'PHP version',
      'php_ini'             => 'php.ini used',
      'project_config_path' => 'Terminus project config',
      'wp_cli_dir_path'     => 'Terminus root dir',
      'wp_cli_version'      => 'Terminus version',
    );
    $this->output()->outputRecord($info, $labels);

  }

  /**
   * Dump the list of global parameters, as JSON.
   *
   * @subcommand param-dump
   */
  function paramDump() {
    $configurator = new Configurator();
    $this->output()->outputDump($configurator->getSpec());
  }

  /**
   * Clear session data
   *
   * @subcommand session-clear
   */
  public function sessionClear() {
    $this->cache->remove('session');
  }

  /**
   * Dump session data
   *
   * @subcommand session-dump
   */
  public function sessionDump() {
    $session = $this->cache->getData('session');
    $this->output()->outputDump($session);
  }

  /**
   * Print Terminus version.
   */
  public function version() {
    $labels = array(
      'version' => 'Terminus version',
      'script'  => 'Terminus script'
    );
    $this->output()->outputRecord(
      array('version' => TERMINUS_VERSION, 'script' => TERMINUS_SCRIPT),
      $labels
    );
  }

  /**
   * Splits command attributes into an array for easy use
   *
   * @param [mixed] $command Command object to render
   * @return [array] $dump
   */
  private function commandToArray($command) {
    $dump = array(
      'name'        => $command->getName(),
      'description' => $command->getShortdesc(),
      'longdesc'    => $command->getLongdesc(),
    );

    foreach ($command->getSubcommands() as $subcommand) {
      $dump['subcommands'][] = $this->commandToArray($subcommand);
    }

    if (empty($dump['subcommands'])) {
      $dump['synopsis'] = (string)$command->getSynopsis();
    }

    return $dump;
  }

}
