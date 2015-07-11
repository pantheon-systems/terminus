<?php
/**
 * High level CLI for managing SSH tunnels.
 */

use Terminus\Utils;
use Terminus\Auth;
use Terminus\Tunnels;
use Terminus\SiteFactory;
use \Terminus\Helpers\Input;


class Tunnels_Command extends Terminus_Command {
  /**
   * Macros for managing SSH tunnels to Pantheon.
   * @package Terminus
   * @version 2.0
   */
  public function __construct() {
    parent::__construct();
    Auth::loggedIn();
    $this->Tunnels = new Tunnels;
  }

  /**
  * List all open tunnels.
  *
  **/
  public function show($args, $assoc_args) {
    $data = $this->Tunnels->get_all();
    if (empty($data)) {
      Terminus::line("No tunnels appear to be open.");
      return;
    }
    $this->handleDisplay($data);
  }

  /**
  * Create a new tunnel to a site/env/service
  *
  * ## OPTIONS
  *
  * [--site=<site>]
  * : site to tunnel to
  *
  * [--env=<env>]
  * : environment to tunnel
  *
  * [--type=<type>]
  * : which container to tunnel to (e.g. 'dbserver')
  *
  * [--port=<port>]
  * : local port to bind to
  *
  * ## EXAMPLES
  *
  **/
  public function create($args, $assoc_args) {
    $site = SiteFactory::instance(Input::site($assoc_args));
    $env = Input::env($assoc_args, 'env');
    $type = Input::optional('type', $assoc_args, 'dbserver');
    $port = Input::optional('port', $assoc_args, null);
    $data = $this->Tunnels->create($site, $env, $type, $port);
    if ($data) {
      Terminus::success('Tunnel created!');
      Terminus::line('');
      $this->handleDisplay($data, array(), array('', 'Tunnel Data')); 
      if ($type == 'dbserver') {
        $binding = $site->find_binding($env, $type);
        Terminus::line('Connection info:');
        Terminus::line(strtr('  mysql -u @username -p@password -h @host -P @port @database',
                             array('@username' => $binding->username, 
                                   '@password' => '<db_password>', 
                                   '@host' => $data['host'], 
                                   '@port' => $data['port'], 
                                   '@database' => $binding->database)));
      }
    } 
  }
  
  /**
  * Close a tunnel.
  *
  * ## OPTIONS
  *
  * [--pid=<pid>]
  * : process id to close
  *
  * [--all]
  * : kill 'em all
  */
  public function close($args, $assoc_args) {
    $pid = Input::optional($assoc_args, 'pid', FALSE);
    $all = array_key_exists('all', $assoc_args);
    if ($all) {
      Terminus::log('Closing all tunnels...');
      $msg = $this->Tunnels->close();  
    }
    elseif ($pid) {
      $msg = $this->Tunnels->close($pid);  
    }
    else {
      $confirmed = Terminus::confirm("Close all tunnels?");
      if ($confirmed) {
        $msg = $this->Tunnels->close();  
      }
    }
    if ($msg) {
      Terminus::success($msg);
    }
  }


}

\Terminus::add_command( 'tunnels', 'Tunnels_Command' );