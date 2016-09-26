<?php

namespace Terminus\Commands;

/**
 * @command drush
 */
class DrushCommand extends CommandWithSSH
{
  /**
   * @inheritdoc
   */
    protected $client = 'Drush';
  /**
   * @inheritdoc
   */
    protected $command = 'drush';
  /**
   * @inheritdoc
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
    public function __invoke($args, $assoc_args)
    {
        parent::__invoke($args, $assoc_args);
        $command = $this->ssh_command;
        if ($this->log()->getOptions('logFormat') != 'normal') {
            $command .= ' --pipe';
        }
        $result = $this->environment->sendCommandViaSsh($command);
        $this->output()->outputDump($result['output']);
        exit($result['exit_code']);
    }
}
