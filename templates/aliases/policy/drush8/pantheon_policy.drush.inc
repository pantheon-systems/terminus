<?php

use Drush\Log\LogLevel;

if ((version_compare(DRUSH_VERSION, '8.3.0') < 0) && (!getenv('DISABLE_PANTHEON_DRUSH_VERSION_WARNING'))) {
  register_shutdown_function(
    function() {
      drush_log(dt('Drush version (' . DRUSH_VERSION . ') is incompatible with Pantheon aliases. Upgrade to Drush 8.3.0 or 9.6.0 or later. (To disable this warning, set the environment variable DISABLE_PANTHEON_DRUSH_VERSION_WARNING=1)'), LogLevel::WARNING);
    }
  );
}

/**
 * Adjust the contents of a site alias.
 */
function pantheon_policy_drush_sitealias_alter(&$alias_record) {
  // If the alias is "remote", but the remote site is
  // the system this command is running on, convert the
  // alias record to a local alias.
  if (isset($alias_record['remote-host'])) {
    $host = $alias_record['remote-host'];
    if (!(preg_match('#^appserver\..*\.drush\.in$#', $host)) || (strpos($host, '{env-name}') !== FALSE)) {
      return;
    }
    $ip = gethostbyname($host);
    // If the return value of gethostbyname equals its input parameter,
    // that indicates failure.
    if ($host == $ip) {
      $aliasName = $alias_record['#name'];
      return drush_set_error('NO_SUCH_ALIAS', "The alias $aliasName refers to a multidev environment that does not exist.");
    }
  }
}
