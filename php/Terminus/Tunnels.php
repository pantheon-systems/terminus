<?php
namespace Terminus;

use Terminus\SiteFactory;
use Terminus\Site;

/**
 * @file
 * Objects for Terminus tunnel support.
 */


class Tunnels {
  /**
   * Helper function to shell out, return status code, handle errors in a 
   * regular way. 
   */
  private function shellout($cmd, &$lines=array()) {
    if (\Terminus::get_config('verbose')) {
      \Terminus::log(sprintf('Running "%s"', $cmd));
    }
    exec($cmd, $lines, $status);
    if ($status !== 0) {
      \Terminus::error(sprintf('Tunnel command "%s" failed with exit code %d', $cmd, $return_var));
      \Terminus::error(implode("\n", $lines));
    }
    return $status;
  }

  /**
   * Helper function to open a new process.
   * Inspired by Drush's drush_shell_proc_open()
   * https://github.com/drush-ops/drush/blob/master/includes/exec.inc
   */
  private function proc_open($cmd) {
    if (\Terminus::get_config('verbose')) {
      \Terminus::log(sprintf('Opening process "%s"', $cmd));
    }
    $process = proc_open($cmd, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
    $proc_status = proc_get_status($process);
    $exit_code = proc_close($process);
    return ($proc_status["running"] ? $exit_code : $proc_status["exitcode"] );
  }

  /**
   * Returns an array of open tunnels.
   */
  public function get_all() {
    $cmd = 'ps -fU $USER |grep "ssh -[f]" | awk \'{print $2, $5, $12, $15}\'';
    $status = $this->shellout($cmd, $lines);
    if ($status == 0) {
      $tunnels = array();
      $sites = new SiteFactory();
      $keys = array('raw', 'pid', 'created', 'port', 'host', 'type', 'environment', 'site_uuid');
      // Tunnels are keyed by port, ports are keyed by env.site for mapping.
      foreach ($lines as $line) {
        $matches = array();
        if (preg_match('/([0-9]{1,8}) ([0-9]{1,2}:[0-9]{2}PM|AM) ([0-9]{1,7}):127.0.0.1:[0-9]{1,7} [A-z0-9_]{3,16}\.[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}@(([A-z0-9_]{3,16})\.([A-z0-9_]{3,16})\.([a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})\.drush.in)/', $line, $matches)) {
          $tunnel = array_combine($keys, $matches);
          if (isset($data['site_uuid'])) {
            $site = $sites->getSiteByUUID($tunnel['site_uuid']);
          }
          unset($tunnel['raw']);
          $tunnels[] = $tunnel;
        }
      }
      return($tunnels);
    }
    return false;
  }

  /**
   * Opens a tunnel if one is not already open.
   */
  public function create($site, $env, $type = 'dbserver', $port = null, $strict = false) {
    $binding = $site->find_binding($env, $type);

    if (!$binding) {
      \Terminus::error(sprintf('Could not find %s binding in %s', $type, $env));
      return false;
    }

    $tunnel = null;
    $port = isset($port) ? $port : $binding->port;
    $rhost = $this->remote_hostname($site->id, $env, $type);
    if ($tunnel = $this->get($port)) {
      if ($rhost == $tunnel['host']) {
        \Terminus::line(sprintf('Tunnel already open to %s:%d.', $tunnel['host'], $tunnel['port']));
      }
      else {
        \Terminus::error(sprintf('Port %d is already in use with a different service or host.', $port));
        return false;
      }
    }
    else {
      $cmd = "ssh -f -N -L {$port}:127.0.0.1:{$binding->port} -p 2222 {$env}.{$site->id}@{$rhost}";
      if (!$strict) {
        // Keep it quiet
        $cmd .= ' -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no';
      }
      $status = $this->proc_open($cmd);

      if ($status == 0 && $tunnel = $this->get($site->id, $env, $type)) {
        \Terminus::log(sprintf('Tunnel opened to %s:%s.', $tunnel['host'], $tunnel['port']));
      }
      else {
        \Terminus::error('Error setting up the tunnel');
        return false;
      }
    }

    return $tunnel;
  }

  /**
   * Kill tunnel(s)
   */
  public function close($pid=null) {
    if (isset($pid)) {
      $cmd = 'kill ' . $pid;
      $msg = strtr('Tunnel closed for pid: @pid', array('@pid' => $pid));
    }
    else {
      $cmd = "ps -fU \$USER | grep 'ssh -[f]' | awk '{print \$2}' | xargs kill";
      $msg = 'All tunnels closed';
    }
    $status = $this->shellout($cmd);
    if ($status == 0) {
      return $msg;
    }
    return false;
  }

  /**
   * Helper function to retrieve an open tunnel.
   */
  public function get($site_uuid_or_port, $environment = NULL, $type = NULL) {
    $tunnels = $this->get_all();
    if (is_numeric($site_uuid_or_port)) {
      foreach($tunnels as $t) {
        if ($t['port'] == $site_uuid_or_port) {
          return $t;
        }
      }
    }
    else {
      $hostname = $this->remote_hostname($site_uuid_or_port, $environment, $type);
      foreach($tunnels as $t) {
        if ($t['host'] == $hostname) {
          return $t;
        }
      }
    }
    return false;
  }


  /**
   * Helper public function to build a key used to identify open tunnels.
   */
  public function remote_hostname($site_uuid, $environment, $type) {
    return $type . '.' . $environment . '.' . $site_uuid . '.drush.in';
  }
}