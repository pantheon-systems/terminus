<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Commands\CommandWithSSH;
use Terminus\Helpers\Input;
use Terminus\Models\Collections\Sites;

class WpCommand extends CommandWithSSH {
  /**
   * Name of client that command will be run on server via
   */
  protected $client = 'WP-CLI';

  /**
   * A hash of commands which do not work in Terminus. The key is the WP-CLI
   * command, and the value is the Terminus equivalent, blank if DNE
   */
  protected $unavailable_commands = array(
    'import' => '',
    'db'     => '',
  );

  /**
   * Invoke `wp` commands on a Pantheon development site
   *
   * <commands>...
   * : The WP-CLI command you intend to run with its arguments, in quotes
   *
   * [--site=<site>]
   * : The name (DNS shortname) of your site on Pantheon
   *
   * [--env=<environment>]
   * : Your Pantheon environment. Default: dev
   *
   */
  public function __invoke($args, $assoc_args) {
    $this->ensureQuotation($args, $assoc_args);
    $command = array_pop($args);
    $this->checkCommand($command);

    $sites       = new Sites();
    $site        = $sites->get(Input::sitename($assoc_args));
    $environment = Input::env(array('args' => $assoc_args, 'site' => $site));
    if (!$site) {
      $this->failure('Command could not be completed. Unknown site specified.');
    }

    /**
     * See https://github.com/pantheon-systems/titan-mt/blob/master/..
     *  ..dashboardng/app/workshops/site/models/environment.coffee
     */
    $server = $this->getAppserverInfo(
      array('site' => $site->get('id'), 'environment' => $environment)
    );

    $this->log()->info(
      'Running wp {cmd} on {site}-{env}',
      array(
        'cmd'   => $command,
        'site'  => $site->get('name'),
        'env'   => $environment
      )
    );
    $result = $this->sendCommand(
      array(
        'server'      => $server,
        'remote_exec' => 'wp',
        'command'     => $command,
      )
    );
    if (Terminus::getConfig('format') != 'normal') {
      $this->output()->outputRecordList($result);
    }
  }

}

Terminus::addCommand('wp', 'WpCommand');
