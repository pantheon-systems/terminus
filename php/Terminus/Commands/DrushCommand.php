<?php

namespace Terminus\Commands;

use Terminus\Commands\CommandWithSSH;
use Terminus\Models\Collections\Sites;

/**
 * @command drush
 */
class DrushCommand extends CommandWithSSH {
  /**
   * {@inheritdoc}
   */
  protected $client = 'Drush';

  /**
   * {@inheritdoc}
   */
  protected $command = 'drush';

  /**
   * {@inheritdoc}
   */
  protected $unavailable_commands = [
    'sql-connect' => 'site connection-info --field=mysql_command',
    'sql-sync'    => '',
  ];

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The Drush command you intend to run with its arguments, in quotes
   *
   * [--site=<site>]
   * : The name (DNS shortname) of your site on Pantheon
   *
   * [--env=<environment>]
   * : Your Pantheon environment. Default: dev
   *
   */
  public function __invoke($args, $assoc_args) {
    $elements = $this->getElements($args, $assoc_args);
    if ($this->log()->getOptions('logFormat') != 'normal') {
      $elements['command']   .= ' --pipe';
    }
    $result = $this->sendCommand($elements);
    $this->output()->outputDump($result);
  }

}
