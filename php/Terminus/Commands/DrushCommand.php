<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Dispatcher;
use Terminus\Utils;
use Terminus\Commands\CommandWithSSH;
use Terminus\Models\Collections\Sites;
use Terminus\Helpers\Input;

class DrushCommand extends CommandWithSSH {
  /**
   * Name of client that command will be run on server via
   */
  protected $client = 'Drush';

  /**
   * A hash of commands which do not work in Terminus. The key is the Drush
   * command, and the value is the Terminus equivalent, blank if DNE
   */
  protected $unavailable_commands = array(
    'sql-connect' => 'site connection-info --field=mysql_connection',
    'sql-sync'    => '',
  );

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * <commands>...
   * : The WP-CLI command you intend to run, with its arguments
   *
   * [--site=<site>]
   * : The name (DNS shortname) of your site on Pantheon
   *
   * [--env=<environment>]
   * : Your Pantheon environment. Default: dev
   *
   */
  public function __invoke($args, $assoc_args) {
    $command = array_pop($args);
    $this->checkCommand($command);

    $sites = new Sites();
    $assoc_args['site'] = Input::sitename($assoc_args);
    $site = $sites->get($assoc_args['site']);
    if (!$site) {
      $this->failure('Command could not be completed. Unknown site specified.');
    }
    $assoc_args['env'] = $environment = Input::env(
      array('args' => $assoc_args, 'site' => $site)
    );
    $server = $this->getAppserverInfo(
      array('site' => $site->get('id'), 'environment' => $environment)
    );

    if (in_array(
      Terminus::getConfig('format'),
      array('bash', 'json', 'silent')
    )) {
      $assoc_args['pipe'] = 1;
    }
    $this->log()->info(
      "Running drush {cmd} on {site}-{env}",
      array(
        'cmd'   => $command,
        'site'  => $site->get('name'),
        'env'   => $environment
      )
    );
    $result = $this->sendCommand(
      array(
        'server'      => $server,
        'remote_exec' => 'drush',
        'command'     => $command,
      )
    );
    if (Terminus::getConfig('format') != 'normal') {
      $this->output()->outputRecordList($result);
    }
  }

}

Terminus::addCommand('drush', 'DrushCommand');
