<?php

/**
 * @file
 * Terminus tunnel support.
 */

/**
 * Returns an array of open tunnels.
 */
function terminus_tunnel_get_all() {
  $tunnels = array('data' => array(), 'ports' => array());
  if (drush_shell_exec('ps -fU $USER |grep "ssh -[f]" | awk \'{print $2, $5, $12, $15}\'')) {
    $sites = json_decode(terminus_user_site_list());
    $lines = drush_shell_exec_output();
    $keys = array('raw', 'pid', 't1', 't2', 'port', 'host', 'rport', 'ruser', 'rhost', 'type', 'environment', 'site_uuid');
    // tunnels are keyed by port, ports are keyed by env.site for mapping.
    foreach ($lines as $line) {
      $matches = array();
      if (preg_match('/([0-9]{1,8}) ([0-9]{1,2}:[0-9]{2})(PM|AM) ([0-9]{1,7}):(127.0.0.1):([0-9]{1,7}) ([A-z0-9_]{3,16}\.[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})@(([A-z0-9_]{3,16})\.([A-z0-9_]{3,16})\.([a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12})\.drush.in)/', $line, $matches)) {
        $tunnel = array_combine($keys, $matches);
        $tunnel['site'] = isset($sites[$tunnel['site_uuid']]) ? $sites[$tunnel['site_uuid']]['information']['name'] : $tunnel['site_uuid'];
        $tunnels['data'][$tunnel['port']] = $tunnel;
        $tunnels['ports'][$tunnel['rhost']] = $tunnel['port'];
      }
    }
  }
  return $tunnels;
}

/**
 * Opens a tunnel if one is not already open.
 */
function terminus_tunnel_open($site_uuid, $environment, $type = 'dbserver', $port = NULL, $print_info = TRUE) {
  // If tunnel exists, display connection info
  $result = terminus_api_site_environment_bindings($site_uuid, $environment, $type);
  $bindings = json_decode($result['json']);
  $binding = reset($bindings);

  if (empty($binding)) {
    return drush_set_error('TERMINUS_TUNNEL_ERROR', dt('Could not find binding for: @type', array('@type' => $type)));
  }

  $tunnel = NULL;
  $port = isset($port) ? $port : $binding->port;
  $rhost = $type . '.' . $environment . '.' . $site_uuid . '.drush.in';
  if ($tunnel = terminus_tunnel_get($port)) {
    if ($rhost == $tunnel['rhost']) {

      drush_log(dt('Tunnel already open to @rhost:@rport.', array('@rhost' => $tunnel['rhost'], '@rport' => $tunnel['rport'])), 'ok');
    }
    else {
      terminus_tunnel_print_info_table(array($tunnel));
      return drush_set_error('TERMINUS_TUNNEL_ERROR', dt('Port @port is already in use with a different service or host.', array('@port' => $port)));
    }
  }
  else {

    $cmd = "ssh -f -N -L {$port}:127.0.0.1:{$binding->port} -p 2222 {$environment}.{$site_uuid}@{$rhost}";
    $status = drush_shell_proc_open($cmd);
    if ($status != 0) {
      return drush_set_error('TERMINUS_SSH_ERROR', dt('An error @code occurred while opening a tunnel with the command `@command`', array('@command' => $cmd, '@code' => $status)));
    }

    if ($tunnel = terminus_tunnel_get($site_uuid, $environment, $type)) {
      drush_log(dt('Tunnel opened to @rhost:@rport.', array('@rhost' => $tunnel['rhost'], '@rport' => $tunnel['rport'])), 'ok');
    }
    else {
      return drush_set_error('TERMINUS_TUNNEL_ERROR', dt('Unable to verify tunnel was created.'));
    }
  }

  $tunnel['binding'] = $binding;
  if ($print_info) {
    terminus_tunnel_print_info_table(array($tunnel));

    // Display type specific tunnel info
    $func = 'terminus_tunnel_info_' . $type;
    if (isset($tunnel) && function_exists($func)) {
      $func($tunnel, $binding);
    }
  }

  return $tunnel;
}

/**
 * Kill tunnel(s)
 */
function terminus_tunnel_close($pid = NULL) {
  $msg = NULL;
  if (isset($pid)) {
    $cmd = 'kill ' . $pid;
    $msg = dt('Tunnel closed for pid: @pid', array('@pid' => $pid));
  }
  else {
    $cmd = "ps -fU \$USER | grep 'ssh -[f]' | awk '{print \$2}' | xargs kill";
    $msg = dt('All tunnels closed');
  }
  drush_shell_exec($cmd);
  drush_log($msg, 'ok');
}

/**
 * Helper function to retrieve an open tunnel.
 */
function terminus_tunnel_get($site_uuid_or_port, $environment = NULL, $type = NULL) {
  $tunnels = terminus_tunnel_get_all();
  $port = NULL;
  if (is_numeric($site_uuid_or_port)) {
    $port = $site_uuid_or_port;
  }
  else {
    $tkey = _terminus_tunnel_key($site_uuid_or_port, $environment, $type);
    $port = $tunnels['ports'][$tkey];
  }
  if (isset($tunnels['data'][$port])) {
    return $tunnels['data'][$port];
  }
  return FALSE;
}

/**
 * Returns a Tunnel array to be used in an info table.
 */
function terminus_tunnel_print_info_table($tunnels) {
  $rows = array();
  $rows[] = array('PID', 'Since', 'Site', 'Env', 'Type', 'Local', 'Remote');
  foreach ($tunnels as $tunnel) {
   $rows[] = array( $tunnel['pid'], $tunnel['t1'] . $tunnel['t2'], $tunnel['site'], $tunnel['environment'], $tunnel['type'],  $tunnel['port'], $tunnel['rport']);
  }
  drush_print('');
  drush_print_table($rows, TRUE);
  drush_print('');
}

/**
 * Prints connection info for a dbserver tunnel.
 */
function terminus_tunnel_info_dbserver($tunnel, $binding = NULL) {
  $rows = array();
  $rows[] = array('Host', 'Port', 'Username', 'Password', 'Database');
  $rows[] = array($tunnel['host'], $tunnel['port'], $binding->username, $binding->password, $binding->database);
  drush_print_table($rows, TRUE);
  drush_print('');
  drush_print(' Command line');
  drush_print(dt('  mysql -u @username -p@password -h @host -P @port @database',
    array('@username' => $binding->username, '@password' => $binding->password, '@host' => $tunnel['host'], '@port' => $tunnel['port'], '@database' => $binding->database))
  );
  drush_print('');
}

/**
 * Helper function to build a key used to identify open tunnels.
 */
function _terminus_tunnel_key($site_uuid, $environment, $type) {
  return $type . '.' . $environment . '.' . $site_uuid . '.drush.in';
}
