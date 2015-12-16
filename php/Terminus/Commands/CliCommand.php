<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Session;
use Terminus\SitesCache;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\Site;
use Terminus\Models\User;

/**
 * Get information about Terminus itself.
 */
class CliCommand extends TerminusCommand {

  public function __construct() {
    parent::__construct();
    $this->sitesCache = new SitesCache();
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
      $this->commandToArray(Terminus::getRootCommand())
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
   * --point=<point>
   * : The index to the current cursor position relative to the beginning of
   *   the command
   */
  public function completions($args, $assoc_args) {
    $line  = substr($assoc_args['line'], 0, $assoc_args['point']);
    $compl = new Terminus\Completions($line);
    $compl->render();
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
      $sitename = $assoc_args['site'];
      $site_id  = $this->sitesCache->findId($sitename);
      $site     = new Site($site_id);
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
    $php_bin = getenv('TERMINUS_PHP_USED');
    if (defined('PHP_BINARY')) {
      $php_bin = PHP_BINARY;
    }

    $runner = Terminus::getRunner();

    $info   = array(
      'php_binary_path'     => $php_bin,
      'php_version'         => PHP_VERSION,
      'php_ini'             => get_cfg_var('cfg_file_path'),
      'project_config_path' => $runner->project_config_path,
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
    $spec = Terminus::getRunner()->getConfigurator()->getSpec();
    $this->output()->outputDump($spec);
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

Terminus::addCommand('cli', 'CliCommand');
