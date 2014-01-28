<?php
 /**
 * @file
 *  Terminus: programmatic access to the Pantheon Platform via Drush.
 */

/**
 * Some constants we use.
 */
define('TERMINUS_HOST', 'terminus.getpantheon.com');
define('TERMINUS_PORT', 443);
date_default_timezone_set('UTC');

/**
 * An easy function to override constants to allow development internally.
 *
 * Also bootstraps the included library functions, which are separate so people
 * can use them outside the context of Drush.
 *
 * TODO: use for other settings.
 */
function terminus_bootstrap($validate = TRUE) {
  // TODO: something to check that terminus is up to date!
  // TODO: More intelligent includes, maybe autoloading?
  include_once 'terminus.backup.api.inc';
  include_once 'terminus.site.api.inc';
  include_once 'terminus.user.api.inc';
  include_once 'terminus.tunnel.inc';
  include_once 'terminus.workflow.api.inc';
  include_once 'terminus.code.api.inc';
  include_once 'terminus.organizations.api.inc';
  include_once 'terminus.art.inc';
  $session_data = terminus_session_data();

  // DRY session validation.
  if ($validate) {
    if (!isset($session_data['user_uuid'])) {
      return drush_set_error("No user UUID found. Please authenticate first using 'drush pauth'");
    }
  }

  // Common command flags.
  $session_data['nocache'] = drush_get_option('nocache');
  $session_data['json_output'] = drush_get_option('json');
  if (isset($session_data['onebox']) || drush_get_option('onebox')) {
    $session_data['onebox'] = TRUE;
  }

  // Expired session.
  if (isset($session_data['session_expire_time']) && time() > $session_data['session_expire_time']) {
    terminus_pantheon_logout();
    drush_set_error('TERMINUS_BOOTSTRAP_SESS_EXPIRED', dt('The Terminus session expired; you must log in again.'));
  }

  return $session_data;
}

/**
 * Implementation of hook_drush_command().
 *
 * List of available terminus commands.
 *
 * @return
 *   An associative array describing your command(s).
 */
function terminus_drush_command() {
  $items = array();

  $items['pantheon-logout'] = array(
    'description' => "Clear any stored session data.",
    'examples' => array(
      'drush plogout',
    ),
    'aliases' => array('plogout'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-auth'] = array(
    'description' => "Authenticate against the Pantheon dashboard. Required before doing anything else.",
    'arguments' => array(
      'email' => 'Email address of dashboard account',
    ),
    'options' => array(
      'password' => array(
        'description' => 'Optional: include password for script-friendly use.',
        'example-value' => 'mypassword',
      ),
    ),
    'examples' => array(
      'drush pauth josh@getpantheon.com --password=mypassword' => 'Get authentication token.',
    ),
    'aliases' => array('pauth'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-sites'] = array(
    'description' => 'List your Pantheon sites.',
    'examples' => array(
      'drush psites --nocache' => 'Get a fresh list of sites.',
    ),
    'aliases' => array('psites'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-attributes'] = array(
    'description' => 'Get attributes for a particular site.',
    'aliases' => array('psite-attr'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => 'The site UUID in question.',
    ),
    'options' => array(
      'attribute' => array(
        'description' => 'Specific attribute that you want to get.',
        'example-value' => 'label',
      ),
    ),
  );

  $items['pantheon-site-branch-list'] = array(
    'description' => 'List Git branches for a particular site.',
    'aliases' => array('psite-blist'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => 'The site UUID in question.',
    ),
  );

  $items['pantheon-site-branch-create'] = array(
    'description' => 'Create Git branch of master for a particular site.',
    'aliases' => array('psite-bcreate'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => 'The site UUID in question.',
    ),
    'options' => array(
      'name' => array(
        'description' => 'Branch name to be created.',
        'example-value' => 'feature-123',
      ),
    ),
  );

  $items['pantheon-site-branch-delete'] = array(
    'description' => 'Delete a Git branch from a particular site.',
    'aliases' => array('psite-bdel'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => 'The site UUID in question.',
    ),
    'options' => array(
      'name' => array(
        'description' => 'Branch name to be deleted.',
        'example-value' => 'feature-123',
      ),
    ),
  );

  $items['pantheon-hostname-add'] = array(
    'description' => "Add a hostname to a site you control.",
    'arguments' => array(
      'site' => 'The site UUID you are working on.',
      'environment' => 'The environment (e.g. "live").',
      'hostname' => 'The hostname (e.g. "www.mysite.com")',
    ),
    'aliases' => array('psite-hostname-add', 'psite-ha'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-hostname-remove'] = array(
    'description' => "Remove a hostname from a site you control.",
    'arguments' => array(
      'site' => 'The site UUID you are working on.',
      'environment' => 'The environemnt (e.g. "live").',
      'hostname' => 'The hostname (e.g. "www.mysite.com")',
    ),
    'aliases' => array('psite-hostname-remove', 'psite-hr'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-hostname-list'] = array(
    'description' => dt('List all hostnames associated with a site.'),
    'arguments' => array(
      'site' => 'The site UUID you are working on.',
      'environment' => 'The environemnt (e.g. "live").',
    ),
    'aliases' => array('psite-hostname-list', 'psite-hl'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-products'] = array(
    'description' => "Get the list of available Drupal product start-states.",
    'aliases' => array('pproducts'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-create'] = array(
    'description' => "Create a new site on Pantheon",
    'arguments' => array(
      'name' => 'Short name of the site to create. Will be part of the URL.',
    ),
    'options' => array(
      'label' => array(
        'description' => 'Human-friendly site label.',
      ),
      'product' => array(
        'description' => 'UUID of the product you want to start with (see pantheon-products).',
      ),
      'organization' => array(
        'description' => 'UUID of an organization you want the site to be a part of.',
      ),
      'nopoll' => array(
        'description' => 'Do not hang around and wait for the site to be ready. Useful for scripting a lot of spin-ups.',
      ),
    ),
    'aliases' => array('psite-create'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-import'] = array(
    'description' => "Import an existing site to Pantheon",
    'arguments' => array(
      'name' => 'Short name of the site to create. Will be part of the URL.',
      'url' => 'URL of Drush Archive.',
    ),
    'options' => array(
      'label' => array(
        'description' => 'Human-friendly site label.',
      ),
      'organization' => array(
        'description' => 'UUID of an organization you want the site to be a part of.',
      ),
      'nopoll' => array(
        'description' => 'Do not hang around and wait for the site to be ready. Useful for scripting a lot of spin-ups.',
      ),
    ),
    'aliases' => array('psite-import'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-delete'] = array(
    'description' => "Delete a site from Pantheon.",
    'arguments' => array(
      'site' => 'UUID of the site you want to delete.',
    ),
    'aliases' => array('psite-delete'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-backups'] = array(
    'description' => "List site backups (and exports).",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
    ),
    'aliases' => array('psite-backups'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-get-backup'] = array(
    'description' => "Get a download link to a specific site backup.",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
      'bucket' => 'Bucket for the backup.',
      'element' => 'Which part of the backup do you want?',
    ),
    'aliases' => array('psite-get-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-make-backup'] = array(
    'description' => "Trigger an on-demand backup for a site/environment.",
    'arguments' => array(
      'site' => 'UUID of the site you want to make a backup for.',
      'environment' => 'The environment (e.g. "live") you want to back up.',
    ),
    'aliases' => array('psite-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-download-backup'] = array(
    'description' => "Download a backup file from a specific site.",
    'arguments' => array(
      'site' => 'UUID of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to download a backup from.',
      'bucket' => 'Bucket for the backup. Use "latest" for the most recent.',
      'element' => 'Which part of the backup do you want? (e.g. database, files, code)',
      'destination' => 'Where would you like the backup?'
    ),
    'aliases' => array('psite-dl-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-load-backup'] = array(
    'description' => "Load db with a backup file from a specific site.",
    'arguments' => array(
      'site' => 'UUID or name of the site you want to get backups for.',
      'environment' => 'The environment (e.g. "live") you want to use a backup from.',
      'bucket' => 'Bucket for the backup. Use "latest" for the most recent.',
    ),
    'aliases' => array('psite-load-backup'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-connection-mode'] = array(
    'description' => "Set or retrieve the connection mode of a specific site/environment.",
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'The dev or multidev environment you would like to target.',
      'mode' => 'Connection mode like to set (e.g., "sftp" or "git"). Leave blank to retrieve the current mode.',
    ),
    'aliases' => array('psite-cmode'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-commit'] = array(
    'description' => 'Commit changes in an on-server-dev environment.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'Environment to commit: a dev or multidev environment',
    ),
    'options' => array(
      'message' => 'Commit message',
    ),
    'aliases' => array('psite-commit'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-diffstat'] = array(
    'description' => 'Get a list of changes (diffstat) to be commited in a remote on-server-dev environment.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'Environment to commit: a dev or multidev environment',
    ),
    'aliases' => array('psite-diffs'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-list'] = array(
    'description' => 'Get a list of site environments.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
    ),
    'aliases' => array('psite-elist'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-create'] = array(
    'description' => 'Create a new multidev site environment.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'Name of multidev site environment. If branch does not exist, it will be created.',
    ),
    'options' => array(
      'source' => 'The source environment (e.g. "live") from which content will be cloned. If omitted, will default to dev.',
    ),
    'aliases' => array('psite-ecreate'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-delete'] = array(
    'description' => 'Delete a multidev site environment.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'Name of multidev site environment.',
    ),
    'aliases' => array('psite-edelete'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-lock'] = array(
    'description' => dt('Lock a site environment.'),
    'arguments' => array(
      'site' => dt('UUID or name of the site.'),
      'environment' => dt('Name of site environment.'),
      'username' => dt('Username for HTTP Basic Auth'),
      'password' => dt('Password for HTTP Basic Auth'),
    ),
    'aliases' => array('psite-elo'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-unlock'] = array(
    'description' => dt('Unlock a site environment.'),
    'arguments' => array(
      'site' => dt('UUID or name of the site.'),
      'environment' => dt('Name of site environment.'),
    ),
    'aliases' => array('psite-eul'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-lock-info'] = array(
    'description' => dt('Get information about whether a site environment is locked.'),
    'arguments' => array(
      'site' => dt('UUID or name of the site.'),
      'environment' => dt('Name of site environment.'),
    ),
    'aliases' => array('psite-eli'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-notifications'] = array(
    'description' => dt('Get a list of notifications for a site.'),
    'arguments' => array(
      'site' => dt('UUID of the site.'),
    ),
    'options' => array(
      'count' => dt('Number of notifications to show'),
      'type' => dt('Filter notifications by type'),
    ),
    'aliases' => array('psite-notifications'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-jobs'] = array(
    'description' => dt('Get a list of jobs for a site.'),
    'arguments' => array(
      'site' => dt('UUID of the site.'),
    ),
    'options' => array(
      'environment' => array(
        'description' => dt('Filter jobs by environment'),
        'example-value' => 'dev',
      ),
      'slot' => array(
        'description' => dt('Filter by slot'),
        'example-value' => 'cache_clear',
      ),
    ),
    'aliases' => array('psite-jobs'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-uuid'] = array(
    'description' => "Get the site UUID based on the name.",
    'arguments' => array(
      'site' => 'Name of the site.',
    ),
    'examples' => array(
      'drush psite-uuid mysite --nocache' => 'Get the UUID of your site.',
    ),
    'required-arguments' => TRUE,
    'aliases' => array('psite-uuid'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-name'] = array(
    'description' => dt('Get the site name from UUID.'),
    'arguments' => array(
      'uuid' => dt('UUID of the site.'),
    ),
    'examples' => array(
      'drush psite-name 12345678-1234-abcd-9876-fedcba09 --nocache' => dt('Get the name of your site.'),
    ),
    'required-arguments' => TRUE,
    'aliases' => array('psite-name'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-dashboard'] = array(
    'description' => "Get the dashboard link for a site.",
    'arguments' => array(
      'site' => 'UUID of the site.',
    ),
    'examples' => array(
      'drush psite-dash mysite -y' => 'Open the dashboard for a site.',
    ),
    'aliases' => array('psite-dash'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-service-level'] = array(
    'description' => "Update the service level for the site.",
    'arguments' => array(
      'site' => 'UUID of the site.',
      'service-level' => 'Service level to upgrade to.',
    ),
    'examples' => array(
      'drush psite-upgrade <site> <service-level>' => 'Open the dashboard for a site.',
    ),
    'aliases' => array('psite-upgrade'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-environment-redis-clear'] = array(
    'description' => 'Clear redis cache of a site environment.',
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'Name of site environment.',
    ),
    'aliases' => array('psite-erc'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  /**
   * Site team functions.
   */
  $items['pantheon-site-team'] = array(
    'description' => "Get the team for a site.",
    'arguments' => array(
      'site' => 'uuid of the site',
    ),
    'examples' => array(
      'drush psite-team 12345678-1234-abcd-9876-fedcba09 --nocache' => 'Get the team of your site.',
    ),
    'aliases' => array('psite-team'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-team-add'] = array(
    'description' => "Add someone to the team for a site.",
    'arguments' => array(
      'site' => 'uuid of the site',
      'user' => 'user you would like to make a part of the team: uuid or email',
    ),
    'examples' => array(
      'drush psite-team-add mysite josh@getpantheon.com' => 'Add josh to your site team.',
    ),
    'aliases' => array('psite-team-add'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-team-remove'] = array(
    'description' => "Remove someone from the team for a site.",
    'arguments' => array(
      'site' => 'uuid of the site',
      'user' => 'user you would like to remove: uuid or email',
    ),
    'examples' => array(
      'drush psite-team-remove mysite josh@getpantheon.com' => 'Remove josh to your site team.',
    ),
    'aliases' => array('psite-team-remove'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-change-owner'] = array(
    'description' => "Change the owner of a site.",
    'arguments' => array(
      'site' => 'uuid of the site',
      'user' => 'team member you would like to make the new owner: uuid or email',
    ),
    'examples' => array(
      'drush psite-set-owner mysite josh@getpantheon.com' => 'Make josh the site owner.',
    ),
    'aliases' => array('psite-change-owner'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  /**
   * Organization-scope functions.
   */
  $items['pantheon-organizations'] = array(
    'description' => "List your organization affiliations.",
    'examples' => array(
      'drush porgs --nocache' => 'Get a fresh list of sites.',
    ),
    'aliases' => array('porgs'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-organization-sites'] = array(
    'description' => "List the sites for an organization. Org admins only.",
    'arguments' => array(
      'organization' => 'UUID of the organization you want to use.',
    ),
    'examples' => array(
      'drush porg-sites --nocache' => 'Get a fresh list of sites.',
    ),
    'aliases' => array('porg-sites'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-organization-site-add'] = array(
    'description' => "Add a site to an organization. Org admins only.",
    'arguments' => array(
      'organization' => 'UUID of the organization.',
      'site' => 'UUID of the organization.',
    ),
    'examples' => array(
      'drush porg-site-add <org> <site>' => 'Add a site to an organization.',
    ),
    'aliases' => array('porg-site-add'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-organization-site-remove'] = array(
    'description' => "Remove a site from an organization. Org admins only.",
    'arguments' => array(
      'organization' => 'UUID of the organization.',
      'site' => 'UUID of the organization.',
    ),
    'examples' => array(
      'drush porg-site-remove <org> <site>' => 'Add a site to an organization.',
    ),
    'aliases' => array('porg-site-remove'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  /**
   * Tunnel Functions.
   */
  $items['pantheon-site-tunnels'] = array(
    'description' => "Get a list of open tunnels.",
    'aliases' => array('psite-tunnels'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-tunnel'] = array(
    'description' => "Opens a tunnel to a specific site/environment/service.",
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'The target environment (e.g. "live").',
      'service' => 'The service (e.g., "mysql" or "redis") to open a tunnel for. Defaults to "mysql".',
      'port' => 'Local port to connect to.',
    ),
    'aliases' => array('psite-tunnel'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-tunnel-close'] = array(
    'description' => "Closes the tunnel to a specific site/environment/service.",
    'arguments' => array(
      'site' => 'UUID or name of the site. If left empty, all tunnels will be closed.',
      'environment' => 'The target environment (e.g. "live"). If left empty, all site tunnels will be closed.',
      'service' => 'The service (e.g., "mysql" or "redis") to open a tunnel for. If empty, all site/environment tunnels will be closed.'
    ),
    'aliases' => array('psite-tclose'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-mount'] = array(
    'description' => "Mounts an environment file system to a local directory.",
    'arguments' => array(
      'site' => 'UUID or name of the site. If left empty, all tunnels will be closed.',
      'environment' => 'The target environment (e.g. "live"). If left empty, all site tunnels will be closed.',
      'destination' => 'Where would you like to mount the file system?'
    ),
    'aliases' => array('psite-mount'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  /**
   * Workflow.
   */
  $items['pantheon-site-clone'] = array(
    'description' => 'Clone content from one site environment to another.',
    'arguments' => array(
      'site' => 'UUID of the site containing content.',
      'source' => 'The source environment (e.g. "live") from which content will be cloned.',
      'target' => 'The target environment (e.g. "live") to which content will be cloned.',
    ),
    'options' => array(
      'db' => 'Clone database content.',
      'files' => 'Clone files content.',
      'update' => 'Run update.php after cloning database.',
    ),
    'examples' => array(
      'drush psite-clone SITE_UUID dev test' => 'Clone both database and files from dev to test.',
      'drush psite-clone SITE_UUID dev test --db --update' => 'Clone only database from dev to test, then run update.php.',
      'drush psite-clone SITE_UUID dev test --files' => 'Clone only user files from dev to test.',
    ),
    'aliases' => array('psite-clone'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-deploy'] = array(
    'description' => 'Deploy code to a particular environment.',
    'arguments' => array(
      'site' => 'UUID of the site.',
      'target' => 'The target environment (e.g. "live") to which code will be deployed.',
    ),
    'options' => array(
      'update' => 'Run update.php after deploying code.',
      'cc' => 'Clear cache after deploying code.',
    ),
    'examples' => array(
      'drush psite-deploy SITE_UUID live' => 'Deploy latest code changes to live.',
      'drush psite-deploy SITE_UUID test --update --cc' => 'Deploy latest code changes to test, the run update.php and clear cache.',
    ),
    'aliases' => array('psite-deploy'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  /**
   * Utility Functions.
   */
  $items['pantheon-aliases'] = array(
    'description' => "Update the Pantheon Drush alias file at ~/.drush/pantheon.aliases.drushrc.php.",
    'options' => array(
      'destination' => array(
        'description' => 'Specify the destination to save the alias file.',
      ),
    ),
    'aliases' => array('paliases'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  $items['pantheon-site-info'] = array(
    'description' => dt('Get Pantheon metadata on a site'),
    'aliases' => array('psite-info'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => dt('UUID or name of the site.'),
    ),
    'options' => array(
      'json' => array(
        'description' => dt('If set, will output in JSON format.'),
      ),
    ),
  );

  $items['pantheon-site-nr-info'] = array(
    'description' => dt('Get New Relic metadata on a site'),
    'aliases' => array('psite-nri'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => dt('UUID or name of the site.'),
    ),
    'options' => array(
      'json' => array(
        'description' => dt('If set, will output in JSON format.'),
      ),
    ),
  );

  $items['pantheon-site-wake'] = array(
    'description' => 'Ensure a site environment is online and not suspended due to inactivity.',
    'aliases' => array('psite-wake'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
    'arguments' => array(
      'site' => 'UUID or name of the site.',
      'environment' => 'The target environment (e.g. "live").',
    ),
  );

  $items['pantheon-pp'] = array(
    'description' => "Direct pseudo-proxy interface. JSON only. For debugging.",
    'arguments' => array(
      'realm' => 'Are you asking about users or sites?',
      'uuid' => 'The unique id of the thing you want to know about.',
      'path' => 'Optional: path extension for more specific commands.',
    ),
    'aliases' => array('pp'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH,
  );

  // Include standard options for all commands:
  $common = array(
    'nocache' => array(
      'description' => 'Force a refresh of cached authentication session.',
    ),
    'json' => array('description' => 'Return raw JSON if possible.'),
    'onebox' => array('description' => 'Use onebox (Pantheon dev only).'),
  );

  foreach ($items as $key => $array) {
    if (isset($array['options'])) {
      $items[$key]['options'] = array_merge($items[$key]['options'], $common);
    }
    else {
      $items[$key]['options'] = $common;
    }
  }

  return $items;
}

/**
 * @group Drush Commands
 *
 * These commands exercise various aspects of the API.
 */

/**
 * Validate site info.
 */
function drush_terminus_pantheon_site_info_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_INFO_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_INFO_NO_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * Site info.
 */
function drush_terminus_pantheon_site_info($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $result = terminus_api_site_info($site_uuid);
  if (drush_get_option('json')) {
    drush_print($result['json']);
  }
  else {
    drush_print_r(json_decode($result['json']));
  }
}

/**
 * Validate New Relic info.
 */
function drush_terminus_pantheon_site_nr_info_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_NR_INFO_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_NR_INFO_NO_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * New Relic info.
 */
function drush_terminus_pantheon_site_nr_info($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $result = terminus_api_new_relic_info($site_uuid);
  if ($result['json'] != 'No NewRelic account found') {
    if (drush_get_option('json')) {
      drush_print($result['json']);
    }
    else {
      drush_print_r(json_decode($result['json']));
    }
  }
  else {
    drush_set_error('DRUSH_PSITE_NR_INFO_NO_NR', dt('Site does not have New Relic enabled.'));
  }
}

/**
 * Validate site environment lock.
 */
function drush_terminus_pantheon_site_environment_lock_validate($site_uuid = FALSE, $environment = FALSE, $username = FALSE, $password = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_LOCK_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_LOCK_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_LOCK_INVALID_ENV', dt('You must specify a valid environment name.'));
    }
  }
  drush_set_option('environment', $environment);

  $result = terminus_api_site_environment_lock_info($site_uuid, $environment);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_LOCK_INFO_REQFAIL', dt('Unable to get lock information.'));
  }
  $lock = json_decode($result['json']);
  if ($lock->locked) {
    return drush_set_error('DRUSH_PSITE_LOCK_ALREADY_LOCKED', dt('Already locked!'));
  }

  if (!$username) {
    if (!$username = drush_prompt(dt('Username'))) {
      return drush_user_abort();
    }
  }
  drush_set_option('username', $username);

  if (!$password) {
    if (!$password = drush_prompt(dt('Password'))) {
      return drush_user_abort();
    }
  }
  drush_set_option('password', $password);
}

/**
 * Lock a site environment.
 */
function drush_terminus_pantheon_site_environment_lock($site_uuid = FALSE, $environment = FALSE, $username = FALSE, $password = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');
  $username = drush_get_option('username');
  $password = drush_get_option('password');

  $result = terminus_api_site_environment_lock($site_uuid, $environment, $username, $password);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_LOCK_REQFAIL', dt('Unable to lock.'));
  }
  drush_log(dt('@site_name @environment is now locked.', array(
    '@site_name' => terminus_pantheon_site_name($site_uuid),
    '@environment' => $environment,
  )), 'ok');
}

/**
 * Validate site environment unlock.
 */
function drush_terminus_pantheon_site_environment_unlock_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_UNLOCK_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_UNLOCK_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_UNLOCK_INVALID_ENV', dt('You must specify a valid environment name.'));
    }
  }
  drush_set_option('environment', $environment);

  $result = terminus_api_site_environment_lock_info($site_uuid, $environment);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_LOCK_INFO_REQFAIL', dt('Unable to get lock information.'));
  }
  $lock = json_decode($result['json']);
  if (!$lock->locked) {
    return drush_set_error('DRUSH_PSITE_UNLOCK_ALREADY_UNLOCKED', dt('Already unlocked!'));
  }
}

/**
 * Unlock a site environment.
 */
function drush_terminus_pantheon_site_environment_unlock($site_uuid = FALSE, $environment = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');

  $result = terminus_api_site_environment_lock_delete($site_uuid, $environment);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_UNLOCK_REQFAIL', dt('Unable to unlock.'));
  }
  drush_log(dt('@site_name @environment is now unlocked.', array(
    '@site_name' => terminus_pantheon_site_name($site_uuid),
    '@environment' => $environment,
  )), 'ok');
}

/**
 * Validate Pantheon site environment lock info.
 */
function drush_terminus_pantheon_site_environment_lock_info_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_LOCK_INFO_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_LOCK_INFO_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_LOCK_INFO_INVALID_ENV', dt('You must specify a valid environment name.'));
    }
  }
  drush_set_option('environment', $environment);
}

/**
 * Get lock info for a site environment.
 */
function drush_terminus_pantheon_site_environment_lock_info($site_uuid = FALSE, $environment = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');

  $result = terminus_api_site_environment_lock_info($site_uuid, $environment);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_LOCK_INFO_REQFAIL', dt('Unable to get lock information.'));
  }
  $lock = json_decode($result['json']);
  if (!$lock->locked) {
    drush_print(dt('@site_name @environment is unlocked.', array(
      '@site_name' => terminus_pantheon_site_name($site_uuid),
      '@environment' => $environment,
    )));
  }
  else {
    drush_print(dt('@site_name @environment is locked with username "@username" and password "@password".', array(
      '@site_name' => terminus_pantheon_site_name($site_uuid),
      '@environment' => $environment,
      '@username' => $lock->username,
      '@password' => $lock->password,
    )));
  }
}

/**
 * Validate Pantheon site wake.
 */
function drush_terminus_pantheon_site_wake_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_WAKE_NO_SESSION', 'You must authenticate before using this command.');
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_WAKE_INVALID_UUID', 'You must specify a valid site UUID.');
  }

  if (!$site_name = terminus_pantheon_site_name($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_WAKE_NO_SITENAME', 'Unable to determine site name from UUID.');
  }
  drush_set_option('site_name', $site_name);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_WAKE_INVALID_ENV', 'You must specify a valid environment name.');
    }
  }
  drush_set_option('environment', $environment);
}

/**
 * Wake a Pantheon site.
 */
function drush_terminus_pantheon_site_wake($site_uuid = FALSE, $environment = FALSE) {
  $site_name = drush_get_option('site_name');
  $environment = drush_get_option('environment');

  $result = terminus_api_site_environment_hostnames($site_uuid, $environment);
  $hostnames = (array) json_decode($result['json']);
  reset($hostnames);
  $hostname = key($hostnames);

  $url = 'http://' . $hostname . '/pantheon_healthcheck';

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_NOBODY, 1);

  if (!$response = curl_exec($ch)) {
    return drush_set_error('DRUSH_PSITE_WAKE_CURL_FAIL', curl_error($ch));
  }

  $headers_exploded = explode("\n", $response);
  $headers = array(
    'status' => $headers_exploded[0],
  );
  array_shift($headers_exploded);

  foreach($headers_exploded as $header) {
    $key_value = explode(':', $header);
    $key = trim($key_value[0]);
    $value = trim($key_value[1]);
    if ($key && ($key != $headers['status'])) {
      $headers[$key] = $value;
    }
  }

  $info = curl_getinfo($ch);
  if ($info['http_code'] != 200) {
    return drush_set_error('DRUSH_PSITE_WAKE_HTTP_FAIL', dt('Request failed: @http_code', array(
      '@http_code' => $info['http_code'],
    )));
  }

  if (!isset($headers['X-Pantheon-Styx-Hostname'])) {
    return drush_set_error('DRUSH_PSITE_WAKE_NO_PHEADERS', 'Pantheon headers missing, which isn\'t quite right.');
  }

  drush_log(dt('@site_name-@environment responded in @total_time seconds.', array(
    '@site_name' => $site_name,
    '@environment' => $environment,
    '@total_time' => $info['total_time'],
  )), 'ok');
}

/**
 * Validate Pantheon site code deploy.
 */
function drush_terminus_pantheon_site_deploy_validate($site_uuid = '', $env_target = '') {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_DEPLOY_NO_SESSION', 'You must authenticate before using this command.');
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_DEPLOY_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  if (!terminus_validate_environment($env_target)) {
    return drush_set_error('DRUSH_PSITE_DEPLOY_INVALID_TARGET', 'You must specify a valid target environment name.');
  }
}

/**
 * Pantheon site code deploy.
 */
function drush_terminus_pantheon_site_deploy($site_uuid = '', $env_target = '') {
  $updatedb = drush_get_option('update') ? 1 : 0;
  $clearcache = drush_get_option('cc') ? 1 : 0;

  // Build label to describe operation.
  $content = array();
  if ($updatedb) {
    $content[] = 'run update.php';
  }
  if ($clearcache) {
    $content[] = 'clear all caches';
  }
  $content_str = implode(' and ', $content);
  if ($content_str) {
    $content_str = ', then ' . $content_str;
  }

  // Confirm.
  $confirm = drush_confirm(dt('Are you sure you want to deploy @site_name code to @target@content_str?', array(
    '@site_name' => terminus_site_uuid_to_name($site_uuid),
    '@content_str' => $content_str,
    '@target' => $env_target,
  )));
  if (!$confirm) {
    return;
  }

  // Deploy code.
  drush_log('Initiated code deployment' . $content_str . '.', 'notice');
  terminus_api_environment_deploy_code($site_uuid, $env_target, $updatedb, $clearcache);
}

/**
 * Validate Pantheon site content clone.
 */
function drush_terminus_pantheon_site_clone_validate($site_uuid, $source, $target) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_CLONE_NO_SESSION', 'You must authenticate before using this command.');
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_CLONE_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  if (!terminus_validate_environment($source)) {
    return drush_set_error('DRUSH_PSITE_CLONE_INVALID_SOURCE', 'You must specify a valid source environment name.');
  }
  if (!terminus_validate_environment($target)) {
    return drush_set_error('DRUSH_PSITE_CLONE_INVALID_TARGET', 'You must specify a valid target environment name.');
  }
  if (!drush_get_option('db') && drush_get_option('files') && drush_get_option('update')) {
    return drush_set_error('DRUSH_PSITE_CLONE_INVALID_UPDATE', dt("You can't update the database if you're not cloning the database."));
  }
}

/**
 * Pantheon site content clone.
 */
function drush_terminus_pantheon_site_clone($site_uuid, $env_source, $env_target) {
  // Figure out the various options.
  $clone_db = $clone_files = FALSE;
  if (!drush_get_option('db') && !drush_get_option('files')) {
    $clone_db = $clone_files = TRUE;
  }
  else {
    if (drush_get_option('db')) {
      $clone_db = TRUE;
    }
    if (drush_get_option('files')) {
      $clone_files = TRUE;
    }
  }
  $clone_db_update = drush_get_option('update') ? 1 : 0;

  // Build label to describe operation.
  $content = array();
  if ($clone_db) {
    if ($clone_db_update) {
      $content[] = 'database (then run update.php)';
    }
    else {
      $content[] = 'database';
    }
  }
  if ($clone_files) {
    $content[] = 'user files';
  }
  $content_str = implode(' and ', $content);

  // Confirm.
  $confirm = drush_confirm(dt('Are you sure you want to clone @site_name @content_str from @source to @target?', array(
    '@site_name' => terminus_site_uuid_to_name($site_uuid),
    '@content_str' => $content_str,
    '@source' => $env_source,
    '@target' => $env_target,
  )));
  if (!$confirm) {
    return;
  }

  // Clone database.
  if ($clone_db) {
    if ($clone_db_update) {
      drush_log('Initiated database cloning then update.php.', 'notice');
    }
    else {
      drush_log('Initiated database cloning.', 'notice');
    }
    terminus_api_environment_clone_database($site_uuid, $env_source, $env_target, $clone_db_update);
  }

  // Clone files.
  if ($clone_files) {
    drush_log('Initiated files cloning.', 'notice');
    terminus_api_environment_clone_files($site_uuid, $env_source, $env_target);
  }
}

/**
 * User data about self.
 */
function drush_terminus_pantheon_whoami() {
  $session_data = terminus_bootstrap();
  // Import to local scope variables.
  extract($session_data);
  drush_print(dt("User UUID: !uuid", array('!uuid' => $user_uuid)));
  drush_print(dt("Email: !email", array('!email' => $email)));
  return TRUE;
}

/**
 * Validation for the list of Pantheon sites.
 */
function drush_terminus_pantheon_sites_validate() {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITES_NO_SESSION', 'You must authenticate before using this command.');
  }
  drush_set_option('session_data', $session_data);
}

/**
 * Get the list of Pantheon sites.
 */
function drush_terminus_pantheon_sites() {
  extract(drush_get_option('session_data'));

  $sites = terminus_user_site_list();
  if (isset($json_output)) {
    drush_print($sites);
    return TRUE;
  }

  if (!terminus_validate_sites($sites)) {
    return drush_set_error('DRUSH_PSITES_INVALID_RESPONSE', 'Unable to parse list of sites.');
  }

  // Display tabular output.
  // TODO: refactor into reusable display functions.
  $sites = json_decode($sites);
  $rows = array();
  foreach ($sites as $site_uuid => $data) {
    $i = $data->information;
    $rows[$i->name] = array($i->name, $i->service_level, $site_uuid);
  }
  ksort($rows);
  array_unshift($rows, array("Site", "Service Level", "UUID"));
  drush_print_table($rows, TRUE);
}

/**
 * Sanitize the machine name of a site.
 *
 * @param $name
 *
 * @return string
 *   Alpha-numeric and dashes, no spaces.
 */
function terminus_site_name_sanitize($name) {
  // Trim.
  $name = trim($name);
  // Lowercase.
  $name = strtolower($name);
  // Replace spaces with dashes.
  $name = preg_replace('/[\s]+/', '-', $name);
  // Alpha-numeric and dash.
  $name = preg_replace('/[^\da-z-]/', '', $name);
  return $name;
}

/**
 * Validation for site import.
 */
function drush_terminus_pantheon_site_import_validate($name = FALSE, $url = FALSE) {
  // Session data, required to know the current user.
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_IMPORT_NO_SESSION', dt('You must authenticate before using this command.'));
  }
  drush_set_option('session_data', $session_data);

  // Human readable label.
  if (!$label = drush_get_option('label')) {
    $label = terminus_session_select_data('site_label', NULL, NULL, NULL, NULL, $name);
  }
  drush_set_option('label', $label);

  // Machine name.
  if (!$name) {
    if (!$name = terminus_session_select_data('site_name', NULL, NULL, NULL, NULL, terminus_site_name_sanitize($label))) {
      return drush_set_error('DRUSH_PSITE_IMPORT_NO_NAME', dt('You must supply a site name!'));
    }
  }
  drush_set_option('name', terminus_site_name_sanitize($name));

  if (!$url) {
    $url = drush_prompt(dt('URL containing Drush archive'), NULL, TRUE);
  }
  if (!filter_var($url, FILTER_VALIDATE_URL)) {
    return drush_set_error('DRUSH_PSITE_IMPORT_URL_MISSING', 'You must provide a valid URL containing the Drush archive to import.');
  }
  $file_headers = @get_headers($url);
  if ($file_headers[0] == 'HTTP/1.1 404 Not Found') {
    return drush_set_error('DRUSH_PSITE_IMPORT_URL_404', 'The Drush Site Archive URL does not exist!');
  }
  else if ($file_headers[0] == 'HTTP/1.1 403 Forbidden') {
    return drush_set_error('DRUSH_PSITE_IMPORT_URL_403', 'Unable to access the Drush Site Archive URL!');
  }
  drush_set_option('url', $url);

  if (!$organization = drush_get_option('organization')) {
    $organization = terminus_session_select_data('organization');
  }
  drush_set_option('organization', $organization);
}

/**
 * Import an existing site via the CLI.
 */
function drush_terminus_pantheon_site_import($name = FALSE, $url = FALSE) {
  $session_data = drush_get_option('session_data');
  extract($session_data);

  $name = drush_get_option('name');
  $organization = drush_get_option('organization');
  $label = drush_get_option('label');
  $url = drush_get_option('url');

  $result_creation = terminus_api_user_site_create($user_uuid, $name, $label, $organization);
  if (!$result_creation) {
    return drush_set_error('DRUSH_PSITE_IMPORT_CREATION_FAILED', 'Site creation for import failed.');
  }

  $site_uuid = json_decode($result_creation['json']);

  // Start import.
  $result_import = terminus_api_site_import_drush_archive($site_uuid, $url);
  if (!$result_import) {
    return drush_set_error('DRUSH_PSITE_IMPORT_FAILED', 'Site creation request succeeded, but import failed.');
  }

  // JSON response.
  if (isset($json_output) && isset($result['json']) && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }

  // Notify the user.
  drush_log(dt('Site is now building.'), "ok");
  drush_print(dt("The new site's UUID is @site_uuid", array(
    '@site_uuid' => $site_uuid,
  )));

  // Opted out of waiting?
  if (drush_get_option('nopoll')) {
    drush_log("No-poll option detected. Check your dashboard to see when this site is ready.");
    return TRUE;
  }

  // Poll for completion.
  drush_print(dt('Waiting for site spinup to complete. You can cancel at any point and the process will continue in the background.'));
  terminus_site_creation_poll($site_uuid);

  // Done!
  drush_print("Your imported site is now ready! Manage it in the dashboard:");
  drush_print("https://dashboard.getpantheon.com/sites/$site_uuid");
  return TRUE;
}

/**
 * Create a new site via the CLI.
 */
function drush_terminus_pantheon_site_create_validate($name = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_CREATE_NO_SESSION', dt('You must authenticate before using this command.'));
  }
  drush_set_option('session_data', $session_data);

  // Human readable label.
  if (!$label = drush_get_option('label')) {
    $label = terminus_session_select_data('site_label', NULL, NULL, NULL, NULL, $name);
  }
  drush_set_option('label', $label);

  // Machine name.
  if (!$name) {
    if (!$name = terminus_session_select_data('site_name', NULL, NULL, NULL, NULL, terminus_site_name_sanitize($label))) {
      return drush_set_error('DRUSH_PSITE_IMPORT_NO_NAME', dt('You must supply a site name!'));
    }
  }
  drush_set_option('name', terminus_site_name_sanitize($name));

  $organization = drush_get_option('organization');
  if ($organization == 'none') {
    $organization = '';
  }
  else if (!$organization) {
    $organization = terminus_session_select_data('organization');
  }
  drush_set_option('organization', $organization);

  if (drush_get_option('product')) {
    $product = drush_get_option('product');
  }
  else if (!$product = terminus_session_select_data('product')) {
    return drush_set_error('DRUSH_PSITE_CREATE_NO_PRODUCT', dt('You must specify a starting state!'));
  }
  drush_set_option('product', $product);
}

/**
 * Create a new site via the CLI.
 */
function drush_terminus_pantheon_site_create($name = FALSE) {
  $session_data = drush_get_option('session_data');
  extract($session_data);

  $name = drush_get_option('name');
  $organization = drush_get_option('organization');
  $label = drush_get_option('label');
  $product = drush_get_option('product');

  drush_log(dt('Queuing build.'), 'ok');
  $result = terminus_api_user_site_create($user_uuid, $name, $label, $organization, $product);
  if (!$result) {
    return drush_set_error('DRUSH_PSITE_CREATE_FAILED', dt('Site creation failed.'));
  }
  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }

  $site_uuid = json_decode($result['json']);
  drush_log(dt('Your new site "@name" is now building with UUID "@site_uuid".', array(
    '@name' => $name,
    '@site_uuid' => $site_uuid,
  )), 'ok');
  drush_cache_clear_all('terminus-sites', 'pantheon', TRUE);
  drush_cache_clear_all('terminus-user-sites-' . $user_uuid, 'pantheon', TRUE);
  if ($organization) {
    drush_cache_clear_all('terminus-org-sites-' . $organization, 'pantheon', TRUE);
  }

  // Poll for completion.
  if (drush_get_option('nopoll')) {
    drush_log(dt('Not polling for completion; check your dashboard to see when this site is ready.'));
    return TRUE;
  }
  drush_print(dt('Waiting for site spin-up to complete. You can cancel at any point and the process will continue in the background.'));

  terminus_site_creation_poll($site_uuid);

  drush_log(dt('Your site is now ready! Manage it from the dashboard:'), 'ok');
  drush_print('https://dashboard.getpantheon.com/sites/' . $site_uuid);
}

/**
 * Because real notifications are for the weak.
 * @param $site_uuid
 */
function terminus_site_creation_poll($site_uuid) {
  $ready = 0;
  while (TRUE) {
    echo '.';
    sleep(5);
    $result = terminus_api_site_bindings($site_uuid);
    $bindings = json_decode($result['json']);
    // Keep track of ready bindings.
    if (!isset($bindings_ready)) {
      foreach ($bindings as $bid => $data) {
        $bindings_ready[$bid] = FALSE;
      }
    }
    foreach ($bindings as $bid => $data) {
      if (isset($data->ready) && $data->ready && !$bindings_ready[$bid]) {
        $bindings_ready[$bid] = TRUE;
        echo ' ' . dt('@type step complete (@ready/@total).', array(
          '@type' => $data->type,
          '@ready' => ++$ready,
          '@total' => count($bindings_ready),
        ));
      }
    }
    $all_ready = TRUE;
    foreach ($bindings_ready as $binding_ready) {
      if (!$binding_ready) $all_ready = FALSE;
    }
    if ($all_ready) {
      break;
    }
  }

  // Wait for the sync code job to complete.
  drush_print("Creating git repository and deploying code to dev.");
  terminus_notification_poll($site_uuid, 'chef_solo_sync_code');
  echo ' ' . dt('Complete!') . "\n";
}

/**
 * @param string $site_uuid
 * @param string $slot
 */
function terminus_job_poll($site_uuid, $slot) {
  // Require a valid UUID.
  if (!terminus_validate_uuid($site_uuid)) {
    return FALSE;
  }
  // Require type.
  if (!$slot) {
    return FALSE;
  }

  while (TRUE) {
    echo '.';
    sleep(5);
    $jobs = terminus_jobs_list($site_uuid, FALSE, $slot);
    foreach ($jobs as $job) {
      if ($job['completed']) {
        if ($job['status'] != 'SUCCESS') {
          return FALSE;
        }
        return TRUE;
      }
    }
  }
}

/**
 * Wait until notifications are cleared.
 * @param string $site_uuid
 * @param string $type
 */
function terminus_notification_poll($site_uuid, $type) {
  // Require a valid UUID.
  if (!terminus_validate_uuid($site_uuid)) {
    return;
  }
  // Require type.
  if (!$type) {
    return;
  }

  while (TRUE) {
    echo '.';
    sleep(5);
    $notifications = terminus_notifications_list($site_uuid, $type);
    // Some jobs won't be listed until they're complete.
    if (in_array($type, array(
      'ellis_clone_database',
      'ellis_clone_files',
    ))) {
      foreach ($notifications as $notification_time => $notification) {
        if (in_array($notification['phase'], array(
          'FINISHED',
          'COMPLETED',
        ))) {
          break;
        }
      }
    }
    else if (count($notifications) > 0) {
      break;
    }
  }
}

/**
 * Validation prior to clearing a site environment's redis cache.
 */
function drush_terminus_pantheon_site_environment_redis_clear_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_EREDIS_NO_SESS', 'You must authenticate before using this command.');
  }

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_EREDIS_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_EREDIS_INVALID_ENV', 'You must specify a valid environment name.');
    }
  }
  drush_set_option('environment', $environment);

  // Make sure that they even have redis.
  $result_bindings = terminus_api_site_environment_bindings($site_uuid, $environment, 'cacheserver');
  $bindings = json_decode($result_bindings['json']);
  $binding_uuids = get_object_vars($bindings);
  if (!count($binding_uuids)) {
    return drush_set_error('DRUSH_PSITE_EREDIS_NO_REDIS', dt('No redis binding for this environment.'));
  }
  drush_set_option('bindings', $bindings);
}

/**
 * Clear a site environment's redis cache.
 */
function drush_terminus_pantheon_site_environment_redis_clear($site_uuid = FALSE, $environment = FALSE) {
  $bindings = drush_get_option('bindings');
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');
  foreach (get_object_vars($bindings) as $binding_uuid => $binding) {
    // Build the command to flush from the application server.
    $command = 'ssh -p 2222 ' . $environment . '.' . $site_uuid;
    $command .= '@appserver.' . $environment . '.' . $site_uuid . '.drush.in ';
    $command .= '"redis-cli -h ' . $binding->host . ' ';
    $command .= '-p ' . $binding->port . ' ';
    $command .= '-a ' . $binding->password . ' ';
    $command .= 'flushall"';

    if (drush_shell_exec($command)) {
      $response = drush_shell_exec_output();
      $last_response = array_pop($response);
      if ($last_response == 'OK') {
        drush_log(dt('Redis flushed on @site_name @environment cacheserver binding @binding_uuid', array(
          '@site_name' => terminus_pantheon_site_name($site_uuid),
          '@environment' => $environment,
          '@binding_uuid' => $binding_uuid,
        )), 'ok');
      }
      else {
        return drush_set_error('DRUSH_PSITE_EREDIS_CLEAR_FAIL', dt('Unable to flush redis on @site_name @environment cacheserver binding @binding_uuid: @response', array(
          '@site_name' => terminus_pantheon_site_name($site_uuid),
          '@environment' => $environment,
          '@binding_uuid' => $binding_uuid,
          '@response' => $last_response,
        )));
      }
    }
    else {
      return drush_set_error('DRUSH_PSITE_EREDIS_EXEC_FAIL', dt('Unable to execute redis cache clear command.'));
    }
  }
}

/**
 * Get a site uuid based on the name.
 */
function drush_terminus_pantheon_site_uuid($site_name) {
  terminus_bootstrap();
  $site_uuid = terminus_get_site_uuid_by_name($site_name);
  drush_print($site_uuid);
  return TRUE;
}

/**
 * Display a site name.
 *
 * @param string $site_uuid
 *   The unique identifier for the site.
 *
 * @return bool
 *   Always successful.
 */
function drush_terminus_pantheon_site_name($site_uuid) {
  drush_print(terminus_pantheon_site_name($site_uuid));
  return TRUE;
}

/**
 * Get a site name.
 *
 * @param $site_uuid
 *   The unique identifier for the site.
 *
 * @return string
 *   The name of the site, or empty if unknown.
 */
function terminus_pantheon_site_name($site_uuid) {
  if (!terminus_validate_uuid($site_uuid)) {
    return '';
  }
  terminus_bootstrap();
  $result = terminus_api_site_info($site_uuid);
  $site = json_decode($result['json']);
  return $site->name;
}

/**
 * Get a dashboard link.
 */
function drush_terminus_pantheon_site_dashboard($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, FALSE)) {
    drush_log('You must specify a site UUID', 'failed');
    return FALSE;
  }

  drush_print("https://dashboard.getpantheon.com/sites/$site_uuid");
  $open = drush_confirm("Would you like to open this link now?");
  if ($open) {
    exec("open https://dashboard.getpantheon.com/sites/$site_uuid");
  }

  return TRUE;
}

/**
 * Delete a site via the CLI.
 */
function drush_terminus_pantheon_site_delete($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, FALSE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }
  $name = terminus_site_uuid_to_name($site_uuid);
  $confirm = drush_confirm("Are you sure you want to delete $name ($site_uuid)?");
  if (!$confirm) {
    return TRUE;
  }

  $result = terminus_api_site_delete($site_uuid);
  if (!$result) {
    return drush_set_error('PSITE_DELETE_API', dt('Unable to delete site, try again?'));
  }
  drush_log(dt('Site deleted.'), 'ok');
  drush_cache_clear_all('terminus-sites', 'pantheon', TRUE);
  return TRUE;
}

/**
 * Validate hostname list.
 */
function drush_terminus_pantheon_hostname_list_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_HOSTNAME_LIST_NO_SESSION', 'You must authenticate before using this command.');
  }
  drush_set_option('session_data', $session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_HOSTNAME_LIST_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_HOSTNAME_LIST_INVALID_ENV', 'You must specify a valid environment name.');
    }
  }
  drush_set_option('environment', $environment);
}

/**
 * List hostnames associated with an environment.
 */
function drush_terminus_pantheon_hostname_list($site_uuid = FALSE, $environment = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');

  $result = terminus_api_site_environment_hostnames($site_uuid, $environment);

  // JSON response.
  if (isset($json_output) && isset($result['json']) && $result['json']) {
    drush_print($result['json']);
    return;
  }

  $hostnames = array_keys((array) json_decode($result['json']));
  asort($hostnames);

  foreach ($hostnames as $hostname) {
    drush_print($hostname);
  }
}

/**
 * Validate hostname add to a site environment.
 */
function drush_terminus_pantheon_hostname_add_validate($site_uuid = FALSE, $environment = FALSE, $hostname = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_HOSTNAME_ADD_NO_SESSION', 'You must authenticate before using this command.');
  }
  drush_set_option('session_data', $session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_HOSTNAME_ADD_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_HOSTNAME_ADD_INVALID_ENV', dt('You must specify a valid environment name.'));
    }
  }
  drush_set_option('environment', $environment);

  if (!terminus_validate_hostname($hostname)) {
    $hostname = drush_prompt(dt('Hostname (e.g. "www.mysite.com") you would like to add'), $hostname, TRUE);
    if (!terminus_validate_hostname($hostname)) {
      return drush_set_error('DRUSH_PSITE_HOSTNAME_ADD_INVALID_HOSTNAME', dt('You must specify a valid hostname.'));
    }
  }
  drush_set_option('hostname', $hostname);
}

/**
 * Add a hostname to a site environment.
 */
function drush_terminus_pantheon_hostname_add($site_uuid = FALSE, $environment = FALSE, $hostname = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $environment = drush_get_option('environment');
  $hostname = drush_get_option('hostname');

  $result = terminus_api_site_environment_add_hostname($site_uuid, $environment, $hostname);
  if ($result === FALSE) {
    return drush_set_error('DRUSH_PSITE_HOSTNAME_ADD_ERROR', dt('Unable to add @hostname to the @site_name @environment environment.', array(
      '@hostname' => $hostname,
      '@site_name' => terminus_site_uuid_to_name($site_uuid),
      '@environment' => $environment,
    )));
  }

  drush_log(dt('Added @hostname to the @site_name @environment environment.', array(
    '@hostname' => $hostname,
    '@site_name' => terminus_site_uuid_to_name($site_uuid),
    '@environment' => $environment,
  )), 'ok');
}

/**
 * Remove a hostname.
 */
function drush_terminus_pantheon_hostname_remove($site_uuid = FALSE, $environment = FALSE, $hostname = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid) {
    $site_uuid = terminus_session_select_data('site_uuid');
    if (!$site_uuid) {
      drush_log('You must supply a site UUID', 'failed');
      return FALSE;
    }
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  if (!$hostname) {
    $hostname = terminus_session_select_data('hostname', $site_uuid, $environment);
    if (!$hostname) {
      drush_log('You must choose a hostname', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_site_environment_delete_hostname($site_uuid, $environment, $hostname);
  if ($result === FALSE) {
    return FALSE;
  }
  drush_log("Removed '$hostname' from the $environment environment", 'ok');
  return TRUE;
}

/**
 * Get a list of backups for a site.
 */
function drush_terminus_pantheon_site_backups($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_backup_catalog($site_uuid, $environment);
  $rows = array();
  $backups = json_decode($result['json']);
  foreach ($backups as $id => $a) {
    if (!isset($a->filename)) {
      continue;
    }
    $parts = explode('_', $id);
    $type = strpos($id, 'backup') ? 'backup' : 'export';
    $size = round($a->size / (1024 * 1024), 2) . ' MB';
    $rows[$a->filename] = array($type, date(DATE_RFC822, $a->timestamp), $parts[2], $parts[0] . '_' . $parts[1], $size);
  }
  krsort($rows);
  if (drush_get_option('json')) {
    drush_print(json_encode($rows));
    return TRUE;
  }
  array_unshift($rows, array("Type", "Date", "Element", "Bucket", "Size"));
  drush_print_table($rows, TRUE);
}

/**
 * Validate backups.
 */
function drush_terminus_pantheon_site_get_backup_validate($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE, $element = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_GET_BACKUP_NO_SESSION', 'You must authenticate before using this command.');
  }
  drush_set_option('session_data', $session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    return drush_set_error('DRUSH_PSITE_GET_BACKUP_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!terminus_validate_environment($environment)) {
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_set_error('DRUSH_PSITE_GET_BACKUP_INVALID_ENV', 'You must specify a valid environment name.');
    }
  }
  drush_set_option('environment', $environment);

  if (!$element) {
    if (!$element = terminus_session_select_data('element')) {
      return drush_set_error('DRUSH_PSITE_GET_BACKUP_INVALID_ELEMENT', 'You must specify a valid element.');
    }
  }
  drush_set_option('element', $element);

  if ($bucket == 'latest') {
    $bucket = terminus_latest_bucket($site_uuid, $environment, $element);
  }
  if (!$bucket) {
    if (!$bucket = terminus_session_select_data('bucket', $site_uuid, $environment, $element)) {
      return drush_set_error('DRUSH_PSITE_GET_BACKUP_INVALID_BUCKET', 'You must specify a valid bucket.');
    }
  }
  drush_set_option('bucket', $bucket);
}

/**
 * Get a list of backups for a site.
 */
function drush_terminus_pantheon_site_get_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE, $element = FALSE) {
  $session_data = drush_get_option('session_data');
  extract($session_data);

  $result = terminus_api_backup_download_url(drush_get_option('site_uuid'), drush_get_option('environment'), drush_get_option('bucket'), drush_get_option('element'));

  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }

  $data = json_decode($result['json']);
  drush_print(dt('Signed URL for the backup (surround the download link in double quotes if using with wget):'));
  drush_print($data->url);
  return TRUE;
}

/**
 * Make a backup. Uses more generic backup api.
 */
function drush_terminus_pantheon_site_make_backup($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if (!$session_data) {
    drush_log('You are not authenticated', 'failed');
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid, TRUE, TRUE)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  $result = terminus_api_site_make_backup($site_uuid, $environment, 'backup');
  drush_print("Backup initiated.");
  return TRUE;
}

/**
 * Download a backup from a site.
 */
function drush_terminus_pantheon_site_download_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE, $element = FALSE, $destination = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  if (!$element) {
    $element = terminus_session_select_data('element');
    if (!$element) {
      drush_log('You must supply an element', 'failed');
      return FALSE;
    }
  }

  // Retrieve the latest bucket
  if ($bucket == 'latest') {
    $bucket = terminus_latest_bucket($site_uuid, $environment, $element);
  }
  if (!$bucket) {
    $bucket = terminus_session_select_data('bucket', $site_uuid, $environment, $element);
    if (!$bucket) {
      drush_log('You must supply a bucket', 'failed');
      return FALSE;
    }
  }
  if (!$destination) {
    $destination = terminus_session_select_data('destination');
    if (!$destination) {
      drush_log('You must supply a destination', 'failed');
      return FALSE;
    }
  }

  $result = terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element);
  $data = json_decode($result['json']);
  $filename = strstr(basename($data->url), '?', '_');

  drush_log("Downloading " . $filename . "...");

  // Copied from pm code
  $cache_duration = 86400*365;
  $path = _drush_download_file($data->url, $destination . DIRECTORY_SEPARATOR . $filename, $cache_duration);
  if ($path || drush_get_context('DRUSH_SIMULATE')) {
    drush_log("Downloading " . $filename . " was successful.");
  }
  else {
    return drush_set_error('DRUSH_PSITE_DOWNLOAD_FAILED', 'Unable to download ' . $filename . ' to ' . $destination . ' from '. $data->url);
  }

  return TRUE;
}

/**
 * Load a database with a backup from a site.
 */
function drush_terminus_pantheon_site_load_backup($site_uuid = FALSE, $environment = FALSE, $bucket = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $element = 'database';

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  // Retrieve the latest bucket
  if ($bucket == 'latest' || (!$bucket && $site_uuid && $environment)) {
    $bucket = terminus_latest_bucket($site_uuid, $environment, $element);
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }
  if (!$bucket) {
    $bucket = terminus_session_select_data('bucket', $site_uuid, $environment, $element);
    if (!$bucket) {
      drush_log('You must supply a bucket', 'failed');
      return FALSE;
    }
  }

  if (!drush_confirm('Overwrite this database with ' . $environment . '.' . $site_uuid . '?')) {
    return drush_user_abort();
  }

  $destination = drush_tempdir();
  $result = terminus_api_backup_download_url($site_uuid, $environment, $bucket, $element);
  $data = json_decode($result['json']);
  $filename = strstr(basename($data->url), '?', '_');

  $cache_duration = 86400*365;
  $path = _drush_download_file($data->url, $destination . DIRECTORY_SEPARATOR . $filename, $cache_duration);
  if (!$path && !drush_get_context('DRUSH_SIMULATE')) {
    return drush_set_error('DRUSH_PSITE_DOWNLOAD_FAILED', 'Unable to download ' . $filename . ' to ' . $destination . ' from '. $data->url);
  }

  drush_log("Downloading " . $filename . " was successful.");

  // TODO: There's got to be a better way to handle a .gz file here
  shell_exec('gunzip ' . $path);
  $path = strstr($path, '.gz', TRUE);

  drush_set_option('file', $path);
  drush_sql_query();

  drush_log("Database load complete.");
  drush_delete_dir($path);

  return TRUE;
}

/**
 * Set or retrieve the connection mode for a site/environment.
 */
function drush_terminus_pantheon_site_connection_mode($site_uuid = FALSE, $environment = FALSE, $cmode = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  // Only prompt for $cmode if the command is run without a site or environment
  $prompt_cmode = !$site_uuid || !$environment;

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  if ($prompt_cmode && !$cmode) {
    $cmode = terminus_session_select_data('cmode', $site_uuid);
  }

  if (!$cmode) {
    $result = terminus_api_site_environment_onserverdev_get($site_uuid, $environment);
    $data = json_decode($result['json']);
    $mode = $data->enabled ? 'SFTP' : 'Git';
    drush_log("Connection mode: " . $mode, 'ok');
    return TRUE;
  }

  $cmode = strtolower($cmode);
  $onserverdevstatus = $cmode == 'sftp' ? TRUE : FALSE;
  terminus_api_site_environment_onserverdev_set($site_uuid, $environment, $onserverdevstatus);

  $mode = $onserverdevstatus ? 'SFTP' : 'Git';
  drush_log('Connection mode set to: ' . $mode, 'ok');
}

/**
 * Validate site attributes get.
 */
function drush_terminus_pantheon_site_attributes_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_ATTR_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid) {
    if (!$site_uuid = terminus_site_input($site_uuid)) {
      return drush_user_abort();
    }
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_COMMIT_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * Get a list of all site attributes.
 */
function drush_terminus_pantheon_site_attributes($site_uuid = FALSE) {
  $result = terminus_api_site_attributes_get(drush_get_option('site_uuid'));
  $attributes = (array) json_decode($result['json']);
  $rows = array();
  foreach ($attributes as $property => $value) {
    // Allow a specific attribute to be returned.
    if (drush_get_option('attribute') == $property) {
      drush_print($value);
      return;
    }
    $rows[] = array(
      $property,
      $value,
    );
  }

  // Add headers.
  array_unshift($rows, array(
    dt('Property'),
    dt('Value'),
  ));

  drush_print_table($rows, TRUE);
}

/**
 * Validate Git branch create.
 */
function drush_terminus_pantheon_site_branch_create_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_BCREATE_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    return drush_user_abort();
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_BCREATE_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  $name = drush_get_option('name');
  if (!$name) {
    $name = drush_prompt(dt('New branch name'), NULL, TRUE);
  }
  if (!terminus_validate_new_branch_name($name)) {
    return drush_set_error('DRUSH_PSITE_BCREATE_INVALID_NAME', 'You must specify a valid new branch name; dev, test, live and master are reserved.');
  }
  drush_set_option('name', $name);
}

/**
 * Git branch create.
 */
function drush_terminus_pantheon_site_branch_create($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $branch_name = drush_get_option('name');
  $result = terminus_api_code_branch_create($site_uuid, $branch_name);

  $json = json_decode($result['json']);
  if (!$json) {
    return drush_set_error('DRUSH_PSITE_BCREATE_FAIL', 'Branch creation failed, typically because it already exists.');
  }
  drush_log(dt('Branch @branch created, commit ID #@commit', array(
    '@branch' => $branch_name,
    '@commit' => $json->{'refs/heads/' . $branch_name},
  )), 'ok');
}

/**
 * Validate Git branch delete.
 */
function drush_terminus_pantheon_site_branch_delete_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_BDELETE_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    return drush_user_abort();
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_BDELETE_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  $branch_name = drush_get_option('name');
  if (!$branch_name) {
    // Select existing branches.
    $branch_name = terminus_session_select_data('branch', $site_uuid);
  }
  if (!terminus_validate_new_branch_name($branch_name)) {
    return drush_set_error('DRUSH_PSITE_BDELETE_INVALID_NAME', 'You must specify a valid target branch name; dev, test, live and master can\'t be deleted and you should feel bad for trying.');
  }
  drush_set_option('branch_name', $branch_name);
}

/**
 * Git branch delete.
 */
function drush_terminus_pantheon_site_branch_delete($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $branch_name = drush_get_option('branch_name');

  if (!drush_confirm(dt('You sure you want to delete @branch_name?', array(
    '@branch_name' => $branch_name,
  )))) {
    return drush_user_abort();
  }

  $result = terminus_api_code_branch_delete($site_uuid, $branch_name);

  $json = json_decode($result['json']);
  if (!$json) {
    return drush_set_error('DRUSH_PSITE_BDELETE_FAIL', 'Branch deletion failed, typically because it doesn\'t exist.');
  }
  drush_log(dt('Branch @branch_name deleted.', array(
    '@branch_name' => $branch_name,
  )), 'ok');
}

/**
 * Validate environment list.
 */
function drush_terminus_pantheon_site_environment_list_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_ELIST_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    return drush_user_abort();
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_ELIST_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * Environment list.
 */
function drush_terminus_pantheon_site_environment_list($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $result = terminus_api_site_environments($site_uuid);

  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }

  $json = json_decode($result['json']);
  // Graceful failure.
  if (!$json) {
    return;
  }

  // Also get the list of code tips as an ad-hoc environment existence check.
  $tips_result = terminus_api_code_tips($site_uuid);
  $tips_json = json_decode($tips_result['json']);

  $environments = array();

  foreach (get_object_vars($json) as $env => $data) {
    $tips_env = ($env == 'dev') ? 'master' : $env;
    if (isset($tips_json->$tips_env)) {
      $environments[] = array(
        $env,
        date(DATE_RFC822, $data->environment_created),
        $data->lock->locked ? 'locked' : 'public',
      );
    }
    else {
      $environments[] = array(
        $env,
        'NOT CREATED',
        'NOT CREATED',
      );
    }
  }

  ksort($environments);
  array_unshift($environments, array('Environment', 'Created', 'Locked'));
  drush_print_table($environments, TRUE);
}

/**
 * Validate multidev environment creation.
 */
function drush_terminus_pantheon_site_environment_create_validate($site_uuid = FALSE, $new_environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_ECREATE_NO_SESSION', 'You must authenticate before using this command.');
  }

  // Site UUID validation.
  if (!$site_uuid) {
    if (!$site_uuid = terminus_site_input($site_uuid)) {
      return drush_user_abort();
    }
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_ECREATE_INVALID_UUID', 'You must specify a valid site UUID.');
  }
  drush_set_option('site_uuid', $site_uuid);

  // New environment validation.
  if (!terminus_validate_environment($new_environment)) {
    if (!$new_environment = drush_prompt(dt('New environment name'))) {
      return drush_set_error('DRUSH_PSITE_ECREATE_INVALID_NEW_ENV', 'You must specify a valid new environment name.');
    }
  }
  if (terminus_is_reserved_branch_name($new_environment)) {
    return drush_set_error('DRUSH_PSITE_ECREATE_RESERVED_ENV', dt('@new_environment is a reserved environment name.', array(
      '@new_environment' => $new_environment,
    )));
  }
  drush_set_option('new_environment', $new_environment);

  // Source environment validation.
  $env_source = drush_get_option('source');
  if ($env_source && !terminus_validate_environment($env_source)) {
    return drush_set_error('DRUSH_PSITE_ECREATE_INVALID_SOURCE', 'You must specify a valid source environment name.');
  }
  if (!$env_source) {
    $env_source = 'dev';
  }
  drush_set_option('source', $env_source);
}

/**
 * Create a multidev environment.
 */
function drush_terminus_pantheon_site_environment_create($site_uuid = FALSE, $new_environment = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $new_environment = drush_get_option('new_environment');

  // Check to see if branch exists.
  $branch_result = terminus_api_code_tips($site_uuid);
  $branches = json_decode($branch_result['json']);

  // If not, create it.
  if (!isset($branches->$new_environment)) {
    drush_log(dt('Creating the @new_environment branch...', array(
      '@new_environment' => $new_environment,
    )), 'ok');
    $result_branch_create = terminus_api_code_branch_create($site_uuid, $new_environment);
    if (!$result_branch_create) {
      return drush_set_error('DRUSH_PSITE_ECREATE_BRANCH_FAIL', 'Unable to create new branch.');
    }
    drush_log('Branch created.');
  }

  // Create the environment.
  drush_print(dt('Creating the @new_environment environment...', array(
    '@new_environment' => $new_environment,
  )), 0, NULL, FALSE);
  $result_env_create = terminus_api_site_environment_create($site_uuid, $new_environment);
  if (!$result_env_create) {
    return drush_set_error('DRUSH_PSITE_ECREATE_FAIL', 'Unable to create new environment.');
  }
  if (terminus_job_poll($site_uuid, 'converge_binding_' . $new_environment)) {
    drush_print(' ' . dt('environment created.'));
  }
  else {
    return drush_set_error(dt('Problem creating environment!'));
  }

  // Clone files...
  $env_source = drush_get_option('source');
  drush_print(dt('Cloning files from @env_source...', array(
    '@env_source' => $env_source,
  )), 0, NULL, FALSE);
  $result_clone_files = terminus_api_environment_clone_files($site_uuid, $env_source, $new_environment);
  drush_log(json_decode($result_clone_files['json']));
  if (TRUE) {
    if (terminus_job_poll($site_uuid, 'clone_files_from_' . $env_source . '_to_' . $new_environment)) {
      drush_print(' ' . dt('files cloned.'));
    }
    else {
      return drush_set_error(dt('Problem cloning files!'));
    }
  }

  // Clone database...
  drush_print(dt('Cloning database from @env_source...', array(
    '@env_source' => $env_source,
  )), 0, NULL, FALSE);
  $result_clone_db = terminus_api_environment_clone_database($site_uuid, $env_source, $new_environment, FALSE);
  drush_log(json_decode($result_clone_db['json']));
  if (TRUE) {
    if (terminus_job_poll($site_uuid, 'clone_database_from_' . $env_source . '_to_' . $new_environment)) {
      drush_print(' ' . dt('database cloned.'));
    }
    else {
      return drush_set_error(dt('Problem cloning database!'));
    }
  }

  drush_log(dt('Environment @new_environment is ready!', array(
    '@new_environment' => $new_environment,
  )), 'ok');
}

/**
 * Validate multidev environment deletion.
 */
function drush_terminus_pantheon_site_environment_delete_validate($site_uuid, $target_environment) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_EDELETE_NO_SESSION', 'You must authenticate before using this command.');
  }

  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_EDELETE_INVALID_UUID', 'You must specify a valid site UUID.');
  }

  if (!terminus_validate_environment($target_environment)) {
    return drush_set_error('DRUSH_PSITE_EDELETE_INVALID_NEW_ENV', 'You must specify a valid target environment name.');
  }
  if (terminus_is_reserved_branch_name($target_environment)) {
    return drush_set_error('DRUSH_PSITE_EDELETE_RESERVED_ENV', 'Why are you trying to delete a reserved environment? You should feel bad for trying.');
  }

  // See if the environment even exists.
  $result = terminus_api_site_environments($site_uuid);
  $json = json_decode($result['json']);
  if (!isset($json->$target_environment)) {
    return drush_set_error('DRUSH_PSITE_EDELETE_PHANTOM_ENV', 'That environment does\'t exist.');
  }
}

/**
 * Delete a multidev environment.
 */
function drush_terminus_pantheon_site_environment_delete($site_uuid, $target_environment) {
  if (!drush_confirm(dt('Are you sure you want to delete @target_environment from @site_name?', array(
    '@site_name' => terminus_site_uuid_to_name($site_uuid),
    '@target_environment' => $target_environment,
  )))) {
    return;
  }

  // Delete the environment.
  drush_log(dt('Deleting the @target_environment environment...', array(
    '@target_environment' => $target_environment,
  )));
  $result_env_delete = terminus_api_site_environment_delete($site_uuid, $target_environment);
  if (!$result_env_delete) {
    return drush_set_error('DRUSH_PSITE_EDELETE_FAIL', 'Unable to delete the environment.');
  }
  drush_log('Environment deleted.', 'ok');
}

/**
 * Validate Git branch list.
 */
function drush_terminus_pantheon_site_branch_list_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_BLIST_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    return drush_user_abort();
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_BLIST_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * Git branch list.
 */
function drush_terminus_pantheon_site_branch_list($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');
  $result = terminus_api_code_tips($site_uuid);

  $json = json_decode($result['json']);
  // Graceful failure.
  if (!$json) {
    return;
  }

  $branches = array();
  foreach (get_object_vars($json) as $branch => $commit_id) {
    if (!in_array($branch, array('test', 'live'))) {
      $branches[] = array($branch, $commit_id);
    }
  }

  ksort($branches);
  array_unshift($branches, array('Branch', 'Commit ID'));
  drush_print_table($branches, TRUE);
}

/**
 * Validate on-server commits.
 */
function drush_terminus_pantheon_site_commit_validate($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_COMMIT_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid) {
    if (!$site_uuid = terminus_site_input($site_uuid)) {
      return drush_user_abort();
    }
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_COMMIT_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);

  if (!$environment) {
    drush_set_option('dev_only', TRUE);
    if (!$environment = terminus_session_select_data('environment', $site_uuid)) {
      return drush_user_abort();
    }
  }
  if (!terminus_validate_environment($environment) || in_array($environment, array('test', 'live'))) {
    return drush_set_error('DRUSH_PSITE_COMMIT_INVALID_SOURCE', dt('You must choose a dev or multi-dev environment.'));
  }
  drush_set_option('environment', $environment);

  // Determine if in SFTP mode.
  $result = terminus_api_site_environment_onserverdev_get($site_uuid, $environment);
  $data = json_decode($result['json']);
  if (!$data->enabled) {
    return drush_set_error('DRUSH_PSITE_COMMIT_NOT_SFTP', dt('The @environment is not in SFTP mode.', array(
      '@environment' => $environment,
    )));
  }

  $diffstat = termins_render_diffstat($site_uuid, $environment);
  if (!$diffstat) {
    return drush_set_error('DRUSH_PSITE_COMMIT_NO_CHANGES', dt('No changes found to commit, aborting.'));
  }

  $message = drush_get_option('message', FALSE);
  if (!$message) {
    $message = drush_prompt(dt('Your commit message'), NULL, TRUE);
    drush_print("Files to be committed:");
    drush_print_table($diffstat, TRUE);
    drush_print("Message:\n");
    drush_print($message, 2);
    $confirm = drush_confirm("\nConfirm this commit?");
    if (!$confirm) {
      return drush_user_abort();
    }
  }
  drush_set_option('message', $message);
  drush_set_option('session_data', $session_data);
}

/**
 * Commit on server changes.
 */
function drush_terminus_pantheon_site_commit($site_uuid = FALSE, $environment = FALSE) {
  $session_data = drush_get_option('session_data');
  extract($session_data);

  $message = drush_get_option('message', '');
  $environment = drush_get_option('environment');
  $site_uuid = drush_get_option('site_uuid');

  drush_log(dt('Triggering commit.'), 'info');
  $result = terminus_api_site_environment_onserverdev_commit($site_uuid, $environment, $message, $user_uuid);
  // Hope it's ok.
  $json = json_decode($result['json']);
  drush_log(trim($json), 'ok');
}

/**
 * Set or retrieve the connection mode for a site/environment.
 */
function drush_terminus_pantheon_site_diffstat($site_uuid = FALSE, $environment = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
  }
  if (!$environment || in_array($environment, array('test', 'live'))) {
    drush_log('You must choose a dev or multi-dev environment', 'failed');
    return FALSE;
  }

  $diffstat = termins_render_diffstat($site_uuid, $environment);
  if (!$diffstat) {
    drush_log('No diffs found.', 'ok');
    return TRUE;
  }

  drush_print_table($diffstat, TRUE);
  return TRUE;
}

/**
 * Helper function to fetch/render a table of diffs, or return FALSE if none.
 */
function termins_render_diffstat($site_uuid, $environment) {
  $result = terminus_api_site_environment_onserverdev_diff_stats_get($site_uuid, $environment);
  $diffstat = json_decode($result['json']);
  $rows = array();
  foreach ($diffstat as $file => $diff) {
    $rows[] = array($file, $diff->status, $diff->deletions, $diff->additions);
  }
  if (count($rows) == 0) {
    return FALSE;
  }
  ksort($rows);
  array_unshift($rows, array("File", "Status", "Deletions", "Additions"));
  return $rows;
}

/**
 * Validate job list command.
 */
function drush_terminus_pantheon_site_jobs_validate($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_PSITE_JOBS_NO_SESSION', dt('You must authenticate before using this command.'));
  }

  if (!$site_uuid) {
    if (!$site_uuid = terminus_site_input($site_uuid)) {
      return drush_user_abort();
    }
  }
  if (!terminus_validate_uuid($site_uuid)) {
    return drush_set_error('DRUSH_PSITE_JOBS_INVALID_UUID', dt('You must specify a valid site UUID.'));
  }
  drush_set_option('site_uuid', $site_uuid);
}

/**
 * Get a list of jobs for a site.
 */
function drush_terminus_pantheon_site_jobs($site_uuid = FALSE) {
  $site_uuid = drush_get_option('site_uuid');

  $environment = drush_get_option('environment');
  $slot = drush_get_option('slot');

  $jobs = terminus_jobs_list($site_uuid, $environment, $slot);

  if (empty($jobs)) {
    return drush_print(dt('No jobs match.'));
  }

  $rows = array();

  foreach ($jobs as $job) {
    $rows[] = array(
      $job['slot'],
      $job['name'],
      $job['env'],
      $job['completed'] ? 'Y' : 'N',
      $job['status'],
      round($job['duration_ms'] / 1000, 2) . 's',
      date(DATE_RFC822, $job['updated']),
    );
  }

  array_unshift($rows, array('Slot', 'Name', 'Env', 'Completed', 'Status', 'Duration', 'Updated'));
  drush_print_table($rows, TRUE);
}

/**
 * Parse and filter jobs for a site.
 *
 * @param string $site_uuid
 *   Site Unique ID.
 * @param string $environment
 *   Optional; filters by site environment.
 * @param string $slot
 *   Optional; filters by slot (like a category).
 *
 * @return array
 *   Jobs sorted by most recent.
 */
function terminus_jobs_list($site_uuid, $environment = FALSE, $slot = FALSE) {
  $response = terminus_api_site_get_jobs($site_uuid);
  if (!$response) {
    return drush_set_error('DRUSH_PSITE_JOBS_EMPTY', dt('No response for site jobs!'));
  }
  $jobs = (array) json_decode($response['json']);

  $rows = array();
  foreach ($jobs as $job_id => $job) {
    // Filter by environment.
    if ($environment && $environment != $job->environment) {
      continue;
    }
    // Filter by slot.
    if ($slot && strpos($job->slot, $slot) === FALSE) {
      continue;
    }
    $job_time = strtotime($job->changed);
    $job_uid = $job_time . '-' . $job->slot;
    $rows[$job_uid] = array(
      'slot' => $job->slot,
      'name' => $job->human_name,
      'env' => $job->environment,
      'completed' => in_array($job->phase, array('FINISHED', 'COMPLETED')) ? TRUE : FALSE,
      'status' => $job->status,
      'duration_ms' => $job->estimated_duration,
      'updated' => $job_time,
    );
  }
  krsort($rows);
  return $rows;
}

/**
 * Get a list of notifications for a site.
 */
function drush_terminus_pantheon_site_notifications($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  $type = drush_get_option('type');

  $notifications = terminus_notifications_list($site_uuid, $type);

  $count = drush_get_option('count');
  if ($count) {
    $notifications = array_slice($notifications, 0, $count);
  }

  $rows = array();
  foreach ($notifications as $notification_time => $notification) {
    $id = substr($notification['id'], strpos($notification['id'], '-', strpos($notification['id'], '-') + 1) + 1);
    $rows[] = array(
      $notification_time,
      $notification['name'],
      $id,
      $notification['status'],
      $notification['phase'],
      round($notification['duration'] / 1000, 2) . 's',
    );
  }

  array_unshift($rows, array('Time', 'Name', 'ID', 'Status', 'Phase', 'Duration'));
  drush_print_table($rows, TRUE);
}

/**
 * Helpful function to fetch and parse down a list of notifications.
 */
function terminus_notifications_list($site_uuid, $type = FALSE) {
  $result = terminus_api_site_notifications($site_uuid);
  $data = json_decode($result['json']);
  $notifications = array();
  foreach ($data as $id => $notification) {
    if (!isset($notification->name) || is_null($notification->name)) {
      continue;
    }
    if ($type) {
      if (strpos($notification->name, $type) === FALSE) {
        continue;
      }
    }
    $notifications[$notification->start] = array(
      'name' => $notification->name,
      'id' => $id,
      'status' => $notification->build->status,
      'phase' => $notification->build->phase,
      'duration' => $notification->build->duration,
    );
  }
  # TODO: munge this list to remove internals, provide human-readable names.
  krsort($notifications);
  return $notifications;
}

/**
 * Allow upgrades and downgrades from the CLI.
 */
function drush_terminus_pantheon_site_service_level($site_uuid = FALSE, $service_level = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $site_uuid = terminus_session_select_data('site_uuid', $site_uuid);
  if (!$site_uuid) {
    return drush_set_error("You must choose a valid site.");
  }
  # TODO: verify owner.
  if (!$service_level) {
    $service_level = terminus_session_select_data('service_level', $site_uuid);
  }
  drush_log("Setting service level for site '". $site_uuid ."' to '". $service_level ."'", "notice");
  $result = terminus_api_site_put_service_level($site_uuid, $service_level);
  if ($result) {
    drush_log("Service level changed.", "ok");
    return TRUE;
  }
  return drush_set_error("Service level change failed.");
}

/**
 * Team functions for a site.
 */

/**
 * Get the team for a site.
 */
function drush_terminus_pantheon_site_team($site_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }
  $result = terminus_api_site_team_get($site_uuid);
  $team = json_decode($result['json']);

  # Build tabular results.
  $rows = array();
  foreach ($team as $user_uuid => $data) {
    $rows[$data->profile->lastname] = array(
      $data->profile->firstname,
      $data->profile->lastname,
      $data->email,
      $user_uuid,
    );
  }
  ksort($rows);
  array_unshift($rows, array('First Name', 'Last Name', 'Email', 'UUID'));
  drush_print_table($rows, TRUE);
}

/**
 * Add someone to the team for a site.
 *
 * $add_user_uuid must be an email address.
 */
function drush_terminus_pantheon_site_team_add($site_uuid = FALSE, $add_user_uuid = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }
  if (!$add_user_uuid) {
    $add_user_uuid = drush_prompt("Please enter the email address of the user you want to add");
  }

  $result = terminus_api_site_team_member_invite($site_uuid, $add_user_uuid, $user_uuid);
  if ($result === FALSE) {
    return drush_set_error("Error adding user!");
  }
  drush_print("Added user to team!");
  return TRUE;
}

/**
 * Remove someone from the team for a site.
 *
 * $remove_user must be a uuid or email.
 */
function drush_terminus_pantheon_site_team_remove($site_uuid = FALSE, $remove_user = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }
  if (!$remove_user) {
    $remove_user = terminus_session_select_data('team', $site_uuid);
  }
  $result = terminus_api_site_team_get($site_uuid);
  $team = json_decode($result['json']);
  $remove = FALSE;
  foreach ($team as $team_member => $data) {
    if ($remove_user === $team_member || $remove_user === $data->email) {
      $remove = $team_member;
      break;
    }
  }
  if (!$remove) {
    drush_log('You must supply a valid user UUID or email address.', 'failed');
    return FALSE;
  }
  $result = terminus_api_site_team_member_remove($site_uuid, $remove);
  if ($result === FALSE) {
    return drush_set_error("Error removing user!");
  }
  drush_print("Removed user from team!");
  return TRUE;
}

/**
 * Assuming you are the owner, transfer that to someone else.
 *
 * $new_owner must be a uuid or email.
 */
function drush_terminus_pantheon_site_change_owner($site_uuid = FALSE, $new_owner = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }
  if (!$new_owner) {
    $new_owner = terminus_session_select_data('team', $site_uuid);
  }
  $result = terminus_api_site_team_get($site_uuid);
  $team = json_decode($result['json']);
  $switch = FALSE;
  foreach ($team as $team_member => $data) {
    if ($new_owner === $team_member || $new_owner === $data->email) {
      $switch = $new_owner;
      break;
    }
  }
  if (!$switch) {
    drush_log('You must supply a valid user UUID or email address for an existing team member.', 'failed');
    return FALSE;
  }
  $result = terminus_api_set_site_owner($site_uuid, $switch);
  if ($result === FALSE) {
    return drush_set_error("Error changing site owner!");
  }
  drush_print("Ownership changed!");
  return TRUE;
}

/**
 * Organization commands.
 */

/**
 * List orgs for a user.
 */
function drush_terminus_pantheon_organizations() {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $result = terminus_api_user_organizations($user_uuid);
  $orgs = (array) json_decode($result['json']);
  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }
  if (count($orgs) == 0) {
    return drush_set_error("You are not a member of any organizations.");
  }
  $rows = array(array('Name', 'Role', 'UUID'));
  foreach ($orgs as $key => $data) {
    $rows[] = array(
      $data->name,
      $data->admin ? 'Admin' : 'User',
      $key
    );
  }
  drush_print_table($rows, TRUE);
  return TRUE;
}

/**
 * List sites for an org. You must be an admin for this to work.
 */
function drush_terminus_pantheon_organization_sites($organization = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  if (!$organization) {
    $organization = terminus_session_select_data('organization');
  }
  $result = terminus_api_user_organization_sites($user_uuid, $organization);
  if (!$result) {
    # This is what happens when you aren't an admin.
    return drush_set_error("You are not an admin for this organization.");
  }
  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }
  $sites = json_decode($result['json']);
  // Display tabular output.
  // TODO: refactor into re-usable display functions.
  $rows = array();
  foreach ($sites as $site_uuid => $i) {
    $rows[$i->name] = array($i->name, $i->service_level, $site_uuid);
  }
  ksort($rows);
  array_unshift($rows, array("Site", "Service Level", "UUID"));
  drush_print_table($rows, TRUE);
}

/**
 * Helper function to determine if a user is an org admin.
 *
 * TODO: Caching.
 */
function termins_user_organization_admin($user_uuid, $organization) {
  $result = terminus_api_user_organizations($user_uuid);
  $organizations = json_decode($result['json']);
  foreach ($organizations as $org_uuid => $data) {
    if ($org_uuid == $organization && isset($data->admin) && ($data->admin)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Displays a list of open tunnels
 */
function drush_terminus_pantheon_site_tunnels() {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $tunnels = terminus_tunnel_get_all();
  terminus_tunnel_print_info_table($tunnels['data']);
}

/**
 * Opens a tunnel for a specific site/environment/service
 */
function drush_terminus_pantheon_site_tunnel($site_uuid = FALSE, $environment = FALSE, $service = 'mysql', $port = NULL, $print_info = TRUE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  $types = array(
    'mysql' => 'dbserver',
    'redis' => 'cacheserver'
  );

  if (!isset($types[$service])) {
    return drush_set_error('TERMINUS_TUNNEL_ERROR', dt('Invalid service: @service. Currently, the only supported services are: @services', array(
      '@service' => $service,
      '@services' => implode(', ', array_keys($types)),
    )));
  }

  return terminus_tunnel_open($site_uuid, $environment, $types[$service], $port, $print_info);
}

/**
 * Close tunnels
 */
function drush_terminus_pantheon_site_tunnel_close($pid = NULL) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  terminus_tunnel_close($pid);
}

/**
 * Mounts the appserver for a specific environment.
 */
function drush_terminus_pantheon_site_mount($site_uuid = FALSE, $environment = FALSE, $destination = FALSE) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  exec('which sshfs', $output, $ret);
  if ($ret !== 0) {
    return drush_set_error("'sshfs' command not found. Please install from http://osxfuse.github.io/");
  }

  $darwin = False;
  $output = array();
  exec('uname', $output, $ret);
  if (is_array($output) && isset($output[0]) && strpos($output[0], 'Darwin') !== False) {
    $darwin = True;
  }

  if (!$site_uuid = terminus_site_input($site_uuid)) {
    drush_log('You must supply a site UUID', 'failed');
    return FALSE;
  }

  if (!$environment) {
    $environment = terminus_session_select_data('environment', $site_uuid);
    if (!$environment) {
      drush_log('You must supply an environment', 'failed');
      return FALSE;
    }
  }

  if (!$destination) {
    $destination = terminus_session_select_data('destination', NULL, NULL, NULL, 'Select a mount destination:', './' . $environment);
    if (!$destination) {
      drush_log('You must supply a mount destination', 'failed');
      return FALSE;
    }
  }

  if (!is_dir($destination)) {
    drush_print(dt("The directory !destination does not exist.", array('!destination' => $destination)));
    if (drush_confirm(dt('Would you like to create it?'))) {
      drush_mkdir($destination, TRUE);
    }
    if (!is_dir($destination)) {
      return drush_set_error('DRUSH_PSITE_MOUNT_DESTINATION', dt('Unable to create destination directory !destination.', array('!destination' => $destination)));
    }
  }

  $user = $environment . '.' . $site_uuid;
  $host = 'appserver.' . $environment . '.' . $site_uuid . '.drush.in';
  $darwin_args = $darwin ? '-o defer_permissions ' : '';
  $cmd = "sshfs " . $darwin_args . "-p 2222 {$user}@{$host}:./ {$destination}";

  drush_shell_exec($cmd);
  $output = drush_shell_exec_output();

  if (empty($output)) {
    drush_log(dt('Mount successful. To unmount, run: umount !destination', array('!destination' => $destination)), 'ok');
  }
  else {
    return drush_set_error('DRUSH_PSITE_MOUNT', dt('Unable to mount: !output', array('!output' => implode("\n", $output))));
  }
}

function drush_terminus_pantheon_aliases_validate() {
  // Require session.
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return drush_set_error('DRUSH_ALIASES_NO_SESSION', dt('You must authenticate before using this command.'));
  }
  drush_set_option('session_data', $session_data);

  // Determine destination.
  $default_destination = FALSE;
  $home = drush_server_home();
  if ($home) {
    $default_destination = drush_normalize_path($home . '/.drush/pantheon.aliases.drushrc.php');
  }
  $destination = drush_get_option('destination', $default_destination);
  if ($destination == FALSE) {
    drush_log('Could not auto-determine destination. Please provide one using the --destination option.', 'failed');
    return FALSE;
  }

  // Ensure destination can be written to.
  $fh = fopen($destination, 'w');
  if ($fh === FALSE) {
    return drush_set_error('DRUSH_ALIASES_NO_WRITE', dt('Cannot write aliases to @destination - verify this location is writable.', array(
      '@destination' => $destination,
    )));
  }
  drush_set_option('fh', $fh);
  drush_set_option('destination', $destination);
}

/**
 * Update drush aliases file.
 */
function drush_terminus_pantheon_aliases() {
  $session_data = drush_get_option('session_data');
  $fh = drush_get_option('fh');

  drush_log('Loading aliases from Pantheon.');

  // This one is a little wonky still. Goes to Drupal.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_COOKIE, $session_data['session']);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $host = TERMINUS_HOST;
  if (isset($session_data['onebox']) && $session_data['onebox']) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $host = 'onebox.getpantheon.com';
  }
  $url = 'https://' . $host . '/users/' . $session_data['user_uuid'] . '/drushrc';
  curl_setopt($ch, CURLOPT_URL, $url);

  $result = curl_exec($ch);
  if (!terminus_validate_palises($result)) {
    return drush_set_error('DRUSH_ALIASES_UNVALIDATED', dt('Unable to fetch the aliases. Try again?'));
  }

  drush_log(dt('Writing aliases to @destination', array(
    '@destination' => drush_get_option('destination'),
  )));
  fwrite($fh, $result);
  fclose($fh);
  drush_log('Pantheon aliases updated.', 'ok');
  drush_invoke('cache-clear', 'drush');
  drush_cache_clear_all('terminus-sites', 'pantheon', TRUE);
  drush_cache_clear_all('terminus-user-sites-' . $user_uuid, 'pantheon', TRUE);

  return TRUE;
}

/**
 * API Debugging command. Try out anything you like.
 */
function drush_terminus_pantheon_pp($realm, $uuid, $path=FALSE) {
  $result = terminus_request($realm, $uuid, $path);
  if ($result === FALSE) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Get the list of products.
 */
function drush_terminus_pantheon_products() {
  $session_data = terminus_bootstrap();
  extract($session_data);
  $cid = 'terminus-products';
  if ($nocache || !$cache = drush_cache_get($cid, 'pantheon')) {
    drush_log('Loading site data from Pantheon.', 'notice');
    $result = terminus_request('public', 'public', 'products');
    if ($result === FALSE) {
      return FALSE;
    }
    $products = $result['json'];
    drush_cache_set($cid, $products, 'pantheon');
  }
  else {
    drush_log('Loaded site data from cache.', 'notice');
    $products = $cache->data;
  }
  if (isset($json_output) && isset($result['json'])  && $result['json']) {
    drush_print($result['json']);
    return TRUE;
  }

  // Display tabular output.
  $products = json_decode($products);
  $rows = array();
  foreach ($products as $product_uuid => $data) {
    $i = $data->attributes;
    $rows[$i->machinename] = array($i->longname, $i->author, $product_uuid);
  }
  ksort($rows);
  array_unshift($rows, array("Product", "Maker", "UUID"));
  drush_print_table($rows, TRUE);
}

/**
 * Destroy all Terminus session data.
 */
function terminus_pantheon_logout() {
  drush_cache_clear_all('*', 'pantheon', TRUE);
}

/**
 * Get rid of the current session.
 */
function drush_terminus_pantheon_logout() {
  terminus_pantheon_logout();
  drush_log('Removed session token and cleared all locally cached data.', 'ok');
}

/**
 * Get or set current session data from cache.
 */
function terminus_session_data($key = FALSE) {
  static $cache = FALSE;
  if (!$cache) {
    $cache = drush_cache_get('terminus-current-session', 'pantheon');
  }
  if (!$cache) {
    // No session data. That's not good.
    return FALSE;
  }
  if ($key && isset($cache->data[$key])) {
    return $cache->data[$key];
  }
  elseif($key) {
    return FALSE;
  }
  return $cache->data;
}

/**
 * Validate cached session data.
 */
function terminus_validate_session($data) {
  $result = terminus_request('user', $data['user_uuid'], 'email');
  if ($result === FALSE) {
    return FALSE;
  }
  $email = @json_decode($result['json']);
  return $email == $data['email'];
}

/**
 * Make a request to the Dashbord's internal API.
 *
 * @param $realm
 *    Permissions realm for data request: currently "user" or "site" but in the
 *    future this could also be "organization" or another high-level business
 *    object (e.g. "product" for managing your app). Can also be "public" to
 *    simply pull read-only data that is not privileged.
 *
 * @param $uuid
 *    The UUID of the item in the realm you want to access.
 *
 * @param $method
 *    HTTP method (verb) to use.
 *
 * @param $data
 *    A native PHP data structure (int, string, arary or simple object) to be
 *    sent along with the request. Will be encoded as JSON for you.
 */
function terminus_request($realm, $uuid, $path = FALSE, $method = 'GET', $data = NULL) {
  static $ch = FALSE;
  if (!$ch) {
    $ch = curl_init();
  }
  $headers = array();
  $session_data = terminus_bootstrap(FALSE);
  $host = TERMINUS_HOST;
  if (isset($session_data['onebox']) && $session_data['onebox']) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $host = 'onebox.getpantheon.com';
  }
  $url = 'https://'. $host . '/terminus.php?' . $realm . '=' . $uuid;
  if ($path) {
    $url .= '&path='. urlencode($path);
  }
  if ($data) {
    // The $data for POSTs, PUTs, DELETEs are sent as JSON.
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
      $data = json_encode(array('data' => $data));
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
      array_push($headers, 'Content-Type: application/json', 'Content-Length: ' . strlen($data));
    }
    // $data for GETs is sent as querystrings.
    else if ($method === 'GET') {
      $url .= '?' . http_build_query($data);
    }
  }
  // Set URL and other appropriate options.
  $opts = array(
    CURLOPT_URL => $url,
    CURLOPT_HEADER => 1,
    CURLOPT_PORT => TERMINUS_PORT,
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_COOKIE => $session_data['session'],
    CURLOPT_HTTPHEADER => $headers,
  );
  curl_setopt_array($ch, $opts);

  // Grab URL and pass it to the browser.
  drush_log('Making API request.');
  drush_log($url);

  $result = curl_exec($ch);
  list($headers_text, $json) = explode("\r\n\r\n", $result, 2);
  // Work around extra 100 Continue headers - http://stackoverflow.com/a/2964710/1895669
  if (strpos($headers_text," 100 Continue") !== FALSE) {
    list($headers_text, $json) = explode("\r\n\r\n", $json , 2);
  }

  if (curl_errno($ch) != 0) {
    $error = curl_error($ch);
    curl_close($ch);
    return drush_set_error('TERMINUS_API_CONNECTION_ERROR', "CONNECTION ERROR: $error");
  }

  $info = curl_getinfo($ch);
  if ($info['http_code'] > 399) {
    drush_log(dt('Request failed: @http_code @json', array(
      '@http_code' => $info['http_code'],
      '@json' => $json,
    )), 'failed');
    // Expired session. Really don't like the string comparison.
    if ($info['http_code'] == 403 && $json == '"Session not found."') {
      terminus_pantheon_logout();
    }
    return FALSE;
  }

  drush_log(dt('Request complete in @seconds seconds.', array(
    '@seconds' => $info['total_time'],
  )));
  drush_log("Response headers:\n"  .$headers_text);
  if ($json) {
    drush_log("JSON response:\n" . $json);
  }

  return array('headers' => $headers_text, 'json' => $json);
}

/**
 * Clean up output so there are consistent service level names.
 */
function _terminus_service_level_name($level) {
  $levels = array(
    'legacy_basic' => 'personal',
    'basic' => 'personal',
    'legacy_pro' => 'pro',
  );
  if (isset($levels[$level])) {
    return $levels[$level];
  }
  return $level;
}

/**
 * Email validation.
 *
 * @param $email
 * @return boolean
 */
function terminus_validate_email($email) {
  return filter_var($email, FILTER_VALIDATE_EMAIL) ? TRUE : FALSE;
}

/**
 * Ensure cURL is enabled.
 */
function drush_terminus_pantheon_auth_validate($email = FALSE) {
  // Check to see if cURL is available.
  if (!in_array('curl', get_loaded_extensions())) {
    return drush_set_error('DRUSH_PSITE_AUTH_NOCURL', 'The version of PHP drush is using in ' . PHP_BINDIR . '/ does not have the cURL extension enabled. If you need to specify a different PHP binary, see https://drupal.org/node/1302418');
  }

  // Check for last-used email.
  if (!$email) {
    $email_cache = drush_cache_get('terminus-last-email', 'pantheon-persist');
    $email = $email_cache->data;
  }

  // Email wasn't set previously.
  if (!$email) {
    $email = drush_prompt(dt('Pantheon account email address'), NULL, TRUE);
  }

  // Actually validate the email in the validation function.
  if (!terminus_validate_email($email)) {
    return drush_set_error('DRUSH_PSITE_AUTH_INVALID_EMAIL', 'A valid email address is required to authenticate.');
  }

  // Store the email for convenience.
  drush_cache_set('terminus-last-email', $email, 'pantheon-persist');

  // Make email available to actual function.
  drush_set_option('email', $email);
  return TRUE;
}

/**
 * Parse form build ID.
 *
 * @param $html
 * @return string
 */
function terminus_pauth_login_get_form_build_id($html) {
  if (!$html) {
    return FALSE;
  }
  // Parse form build ID.
  $DOM = new DOMDocument;
  @$DOM->loadHTML($html);
  $login_form = $DOM->getElementById('atlas-login-form');
  if (!$login_form) {
    return drush_set_error("Dashboard unavailable", "Dashboard unavailable: login endpoint not found.");
  }

  foreach ($login_form->getElementsByTagName('input') as $input) {
    if ($input->getAttribute('name') == 'form_build_id') {
      return $input->getAttribute('value');
    }
  }
  return FALSE;
}

/**
 * Parse session expiration out of a header.
 * @param $session_header
 * @return int
 */
function terminus_pauth_get_session_expiration_from_header($session_header) {
  $session_info = explode('; ', $session_header);
  foreach ($session_info as $pair) {
    if (strpos($pair, 'expires') === 0) {
      $expiration = explode('=', $pair);
      return strtotime($expiration[1]);
    }
  }
}

/**
 * Parse session out of a header.
 * @param $header
 * @return string
 */
function terminus_pauth_get_session_from_header($header) {
  $session = FALSE;
  $set_cookie = explode('; ', $header);
  foreach ($set_cookie as $cookie) {
    if (strpos($cookie, 'SSESS') === 0) {
      $session = $cookie;
    }
  }
  return $session;
}

/**
 * Parse user ID out of headers.
 * @param $headers
 * @return string
 */
function terminus_pauth_get_user_uuid_from_headers($headers) {
  $location_header = terminus_parse_drupal_headers($headers, 'Location');
  if (!$location_header) {
    return FALSE;
  }
  // https://terminus.getpantheon.com/users/UUID
  return array_pop(explode('/', $location_header));
}

/**
 * Start a dashboard session.
 *
 * It doesn't follow the normal pattern since it's working off Drupal's login
 * forms directly. This will be refactored when there's a direct CLI auth
 * mechanism in the API itself.
 *
 * Many thanks to Amitai and the gang at: https://drupal.org/node/89710
 */
function drush_terminus_pantheon_auth($email = FALSE) {
  if (!$email) {
    $email = drush_get_option('email');
  }
  // Flush existing data.
  drush_cache_clear_all('*', 'pantheon', TRUE);
  $session_data = terminus_bootstrap(FALSE);

  $nocache = drush_get_option('nocache');
  if (!$nocache && isset($session_data['email'])) {
    drush_log(dt('Cached auth found for !email', array('!email' => $email)), 'notice');
    if (terminus_validate_session($session_data)) {
      drush_log('Session still valid. Good to go!', 'ok');
      return TRUE;
    }
  }
  $show_art = FALSE;
  $password = drush_get_option('password');
  if (!$password) {
    $password = drush_prompt(dt('Pantheon dashboard password for !email', array(
      '!email' => $email,
    )), NULL, TRUE, TRUE);
    $show_art = TRUE;
  }
  drush_log(dt('Authenticating as !email', array('!email' => $email)), 'ok');

  $ch = curl_init();
  $host = TERMINUS_HOST;
  if (isset($session_data['onebox']) && $session_data['onebox']) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    $host = 'onebox.getpantheon.com';
  }
  $url = 'https://'. $host .'/login';
  drush_log(dt('POSTing auth to: !url', array('!url' => $url)));
  // Set URL and other appropriate options.
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  // Grab URL and pass it to the browser.
  $result = curl_exec($ch);

  if (curl_errno($ch) != 0) {
    $err = curl_error($ch);
    curl_close($ch);
    return drush_set_error("Dashboard unavailable", "Dashboard unavailable: $err");
  }

  $form_build_id = terminus_pauth_login_get_form_build_id($result);

  // Attempt to log in.
  $login_data = array(
    'email' => $email,
    'password' => $password,
    'form_build_id' => $form_build_id,
    'form_id' => 'atlas_login_form',
    'op' => 'Login',
  );
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
  curl_setopt($ch, CURLOPT_HEADER, 1);
  $result = curl_exec($ch);

  if (curl_errno($ch) != 0) {
    $err = curl_error($ch);
    return drush_set_error("Dashboard unavailable", "Dashboard unavailable: $err");
  }

  $set_cookie_header = terminus_parse_drupal_headers($result, 'Set-Cookie');
  if (!$set_cookie_header) {
    return drush_set_error("Failure!", 'Authentication failed. Please check your credentials and try again.');
  }

  $session = terminus_pauth_get_session_from_header($set_cookie_header);

  if (!$session) {
    return drush_set_error("Failure!", 'Session not found. Please check your credentials and try again.');
  }

  // Get the UUID.
  $user_uuid = terminus_pauth_get_user_uuid_from_headers($result);
  if (!terminus_validate_uuid($user_uuid)) {
    return drush_set_error("Failure!", 'Could not determine user UUID. Please check your credentials and try again.');
  }

  drush_log('Success!', 'ok');
  drush_log(dt('User UUID: !user_uuid', array(
    '!user_uuid' => $user_uuid,
  )), 'notice');

  // Prepare credentials for storage.
  $data = array(
    'user_uuid' => $user_uuid,
    'session' => $session,
    'session_expire_time' => terminus_pauth_get_session_expiration_from_header($set_cookie_header),
    'email' => $email,
  );

  // Pantheon internal development.
  if ($session_data['onebox']) {
    // Mark this session as "forever onebox."
    $data['onebox'] = TRUE;
  }

  // Store the session in drush cache.
  drush_cache_set('terminus-current-session', $data, 'pantheon');

  // Close cURL resource, and free up system resources.
  curl_close($ch);

  if ($show_art) {
    // ASCII ART FTW
    $green = "\033[1;32;40m\033[1m%s\033[0m";
    drush_print("\n\n");
    echo sprintf($green, terminus_art_fist());
    drush_print("\n\n");
    drush_print("LOGIN SUCCESSFUL.");
  }
}

/**
 * Helper function for parsing Drupal headers for login.
 */
function terminus_parse_drupal_headers($result, $target_header='Set-Cookie') {
  // Check that we have a 302 and a session.
  list ($headers_text, $html) = explode("\r\n\r\n", $result, 2);
  if (strpos($headers_text, "100 Continue") !== FALSE) {
    list ($headers_text, $html) = explode("\r\n\r\n", $html , 2);
  }
  $header_lines = explode("\r\n", $headers_text);
  $status = array_shift($header_lines);
  if (strpos($status, "302 Moved Temporarily") === FALSE) {
    return FALSE;
  }
  $headers = array();
  foreach ($header_lines as $line) {
    $parts = explode(': ', $line);
    if (isset($parts[1])) {
      $headers[$parts[0]] = $parts[1];
    }
  }
  if (isset($headers[$target_header])) {
    return $headers[$target_header];
  }

  return FALSE;
}

/**
 * Provides user prompt for missing data.
 * @param $key string Required data name. i.e. site_uuid, environment, etc.
 * @return string input data
 * @todo Use an options array instead of death by parameters.
 */
function terminus_session_select_data($key, $site_uuid = NULL, $environment = NULL, $element = NULL, $prompt_text = NULL, $default = NULL) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  switch ($key) {
    case 'site_uuid':
      $sites = json_decode(terminus_user_site_list());

      $choices = array();
      foreach ($sites as $site => $data) {
        // If a site_uuid was supplied, and it matches, validate.
        if ($site_uuid == $site) {
          return $site_uuid;
        }
        // If a name matches, validate.
        if ($data->information->name == $site_uuid) {
          return $site;
        }
        $i = $data->information;
        $choices[$site] = array($i->name, $i->service_level, $site);
      }

      $val = drush_choice($choices, 'Select a site.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'branch':
      if (!$site_uuid) {
        return;
      }
      $result = terminus_api_code_tips($site_uuid);
      if (!$tips = json_decode($result['json'])) {
        return;
      }

      $branches = array();
      foreach (get_object_vars($tips) as $branch => $commit_id) {
        if (!terminus_is_reserved_branch_name($branch)) {
          $branches[$branch] = $branch;
        }
      }
      return drush_choice($branches, 'Select the target branch.');
      break;

    case 'environment':
      if ($site_uuid) {
        $request = terminus_api_site_environments($site_uuid);
        $environments = json_decode($request['json']);
        $choices = array();
        $dev_only = drush_get_option('dev_only', FALSE);
        foreach ($environments as $env => $data) {
          if (!$dev_only || !in_array($env, array('test', 'live'))) {
            $choices[$env] = $env;
          }
        }
        if (count($choices) == 1) {
          return array_pop($choices);
        }
      }
      else {
        $choices = array('dev' => 'dev', 'test' => 'test', 'live' => 'live');
      }
      $val = drush_choice($choices, 'Select an environment.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'element':
      $choices = array('database' => 'database', 'files' => 'files', 'code' => 'code');
      $val = drush_choice($choices, 'Backup type.', '!key');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'bucket':
      $result = terminus_api_backup_catalog($site_uuid, $environment);
      $choices = array();
      $buckets = array();
      $backups = json_decode($result['json']);

      foreach ($backups as $id => $a) {
        $parts = explode('_', $id);

        if (!isset($a->filename) || $parts[2] != $element) {
          continue;
        }
        $size = round($a->size / (1024 * 1024), 2) . ' MB';
        $choices[$a->filename] = $a->filename . ' ' . $size;
        $buckets[$a->filename] = $parts[0] . '_' . $parts[1];
      }
      krsort($choices);
      $val = drush_choice($choices, 'Select a backup.');
      if ($val !== FALSE) {
        return $buckets[$val];
      }
      break;

    case 'cmode':
      $choices = array(
        0 => 'Retrieve connection mode',
        'sftp' => 'SFTP',
        'git' => 'Git',
      );
      return drush_choice($choices, 'Connection mode');
      break;

    case 'destination':
      $val = drush_prompt(isset($prompt_text) ? $prompt_text : 'Select a destination', isset($default) ? $default : './');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'hostname':
      if ($site_uuid && $environment) {
        $result = terminus_api_site_environment_hostnames($site_uuid, $environment);
        $hostnames = json_decode($result['json']);
        $choices = array();
        foreach ($hostnames as $hostname => $data) {
          $choices[$hostname] = $hostname;
        }
        krsort($choices);
        $val = drush_choice($choices, 'Select a hostname.');
        if ($val !== FALSE) {
          return $val;
        }
      }
      break;

    case 'site_name':
      $val = drush_prompt('Machine name of the site; used as part of the default URL', $default);
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'site_label':
      $val = drush_prompt('Human readable label for the site', $default);
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'organization':
      $result = terminus_api_user_organizations($user_uuid);
      $organizations = json_decode($result['json']);
      if (count((array) $organizations) == 0) {
        return NULL;
      }
      $choices = array('none' => dt('None'));
      foreach ($organizations as $organization => $data) {
        $choices[$organization] = $data->name;
      }
      $val = drush_choice($choices, 'Organizational affiliation');
      if ($val == 'none') {
        return NULL;
      }
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'product':
      # TODO: abstract this into a happy fetcher functon.
      # TODO: allow that to filter by org so interactive spinup can't fail.
      $result = terminus_request('public', 'public', 'products');
      $products = json_decode($result['json']);
      $choices = array();
      foreach ($products as $product_uuid => $data) {
        $i = $data->attributes;
        $choices[$product_uuid] = $i->longname;
        if ($i->author != '') {
          $choices[$product_uuid] .= ' (by '. $i->author .')';
        }
      }
      $val = drush_choice($choices, 'Select your starting state.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'service_level':
      # TODO: Add an api call so the range of possible service-level can be
      # discovered remotely.
      $choices = array(
        'free' => 'free',
        'basic' => 'personal',
        'pro' => 'pro',
        'business' => 'business',
        'elite' => 'elite'
      );
      if ($site_uuid) {
        $result = terminus_api_site_info($site_uuid);
        $site_info = json_decode($result['json']);
        if (isset($site_info->organization)) {
          if (termins_user_organization_admin($user_uuid, $site_info->organization)) {
            $choices['enterprise'] = 'enterprise';
          }
        }
      }
      $val = drush_choice($choices, 'Select the service-level you would like to use.');
      if ($val !== FALSE) {
        return $val;
      }
      break;

    case 'organization':
      $result = terminus_api_user_organizations($user_uuid);
      $organizations = json_decode($result['json']);
      $choices = array();
      foreach ($organizations as $org_uuid => $data) {
        $choices[$org_uuid] = $data->name;
      }
      $val = drush_choice($choices, 'Select the organization you would like to use.');
      if ($val !== FALSE) {
        return $val;
      }
      break;
    case 'team':
      if (!$site_uuid) {
        return drush_set_error("Can't pick from a team without a site!");
      }
      $result = terminus_api_site_team_get($site_uuid);
      $team = json_decode($result['json']);
      $choices = array();
      foreach ($team as $team_uuid => $data) {
        $choices[$team_uuid] = $data->profile->firstname .' '. $data->profile->lastname .' ('. $data->email .')';
      }
      $val = drush_choice($choices, 'Select the team-member you want.');
      if ($val !== FALSE) {
        return $val;
      }
      break;
  }
}

/**
 * Helper function to return the "latest" bucket for a given site_uuid, environment, and element
 */
function terminus_latest_bucket($site_uuid, $environment, $element = NULL) {
  $result = terminus_api_backup_catalog($site_uuid, $environment);
  $buckets = array();
  $backups = json_decode($result['json']);
  foreach ($backups as $id => $a) {
    $parts = explode('_', $id);
    if (!isset($a->filename) || $parts[2] != $element) {
      continue;
    }
    $buckets[$a->filename] = $parts[0] .'_'. $parts[1];
  }
  arsort($buckets);
  return reset($buckets);
}

/**
 * Helper function to handle site UUID input.
 */
function terminus_site_input($site_input = FALSE, $prompt = TRUE, $uuid_only = FALSE) {
  if (!terminus_validate_uuid($site_input) && $site_input) {
    // Try to guess based on name.
    $guessed_uuid = terminus_get_site_uuid_by_name($site_input);
    if (terminus_validate_uuid($guessed_uuid)) {
      return $guessed_uuid;
    }
  }
  if (!$site_input && $prompt) {
    $site_input = terminus_session_select_data('site_uuid');
  }
  if (terminus_validate_uuid($site_input)) {
    return $site_input;
  }
  if (empty($site_input) || $uuid_only) {
    return FALSE;
  }
  return terminus_get_site_uuid_by_name($site_input);
}

/**
 * Helper function to return the site_uuid from the name.
 */
function terminus_get_site_uuid_by_name($site_name) {
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);

  $user_sites = json_decode(terminus_user_site_list());
  foreach ($user_sites as $site_uuid => $data) {
    $i = $data->information;
    if ($i->name == $site_name) {
      return $site_uuid;
    }
  }
  # Get all the orgs where they are an admin.
  $result = terminus_api_user_organizations($user_uuid);
  $orgs = json_decode($result['json']);
  foreach ($orgs as $org => $data) {
    if ($data->admin) {
      $check_orgs[] = $org;
    }
  }
  # Now iterate and see if there's a match.
  foreach ($check_orgs as $organization_uuid) {
    $org_sites = json_decode(terminus_org_site_list($organization_uuid));
    foreach ($org_sites as $site_uuid => $data) {
      if ($data->name == $site_name) {
        return $site_uuid;
      }
    }
  }

  return FALSE;
}

/**
 * Helper function to leverage cache and static sites array.
 */
function terminus_user_site_list($reset = FALSE) {
  static $asites;
  if (isset($asites) && !$reset) {
    return $asites;
  }

  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $cid = 'terminus-user-sites-'. $user_uuid;

  if (!$reset && !isset($nocache) && $cache = drush_cache_get($cid, 'pantheon')) {
    drush_log('Loaded user site list from cache.', 'notice');
    $asites = $cache->data;
    return $asites;
  }

  drush_log('Loading site data from Pantheon.', 'notice');
  $result = terminus_api_user_site_list($user_uuid);
  if ($result === FALSE) {
    return FALSE;
  }
  $asites = $result['json'];
  drush_cache_set($cid, $asites, 'pantheon');

  return $asites;
}

/**
 * Helper function to get org site lists.
 */
function terminus_org_site_list($organization_uuid, $reset = FALSE) {
  static $osites;
  if (isset($osites[$organization_uuid]) && !$reset) {
    return $osites[$organization_uuid];
  }
  $osites = array();
  $session_data = terminus_bootstrap();
  if ($session_data === FALSE) {
    return FALSE;
  }
  extract($session_data);
  $cid = 'terminus-org-sites-'. $organization_uuid;
  if (!$reset && !isset($nocache) && $cache = drush_cache_get($cid, 'pantheon')) {
    drush_log('Loaded organization site list from cache.', 'notice');
    $osites[$organization_uuid] = $cache->data;
    return $osites[$organization_uuid];
  }

  $result = terminus_api_user_organization_sites($user_uuid, $organization_uuid);
  if ($result === FALSE) {
    return FALSE;
  }
  $osites[$organization_uuid] = $result['json'];
  drush_log('Caching organization site list.', 'notice');
  drush_cache_set($cid, $osites[$organization_uuid], 'pantheon');
  return $osites[$organization_uuid];
}

/**
 * Validate Atlas UUID.
 * @param $uuid
 * @return boolean
 */
function terminus_validate_uuid($uuid) {
  return preg_match('#^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$#', $uuid) ? TRUE : FALSE;
}

/**
 * Check to see if a branch name is reserved by Pantheon.
 * @param $branch_name
 * @return bool
 */
function terminus_is_reserved_branch_name($branch_name) {
  return in_array($branch_name, array(
    'dev',
    'test',
    'live',
    'master',
  ));
}

/**
 * Validate Branch name.
 *
 * @param $branch_name
 *   String containing the name of the new branch.
 * @return boolean
 */
function terminus_validate_new_branch_name($branch_name) {
  if (terminus_is_reserved_branch_name($branch_name)) {
    return FALSE;
  }
  return terminus_validate_environment($branch_name);
}

/**
 * Validate Environment Name.
 * @param $env
 * @return bool
 * @todo Actually check to see if the environment exists.
 */
function terminus_validate_environment($env) {
  return preg_match('/^[-a-z0-9]{1,11}$/', $env) ? TRUE : FALSE;
}

/**
 * Helper function to convert a site_uuid to the human-readable name.
 * @param $site_uuid
 * @return mixed
 */
function terminus_site_uuid_to_name($site_uuid) {
  $sites = json_decode(terminus_user_site_list());
  $site_names = array();
  foreach ($sites as $uuid => $data) {
    if ($site_uuid === $uuid) {
      return $data->information->name;
    }
  }
  return drush_set_error("No site found for UUID '$site_uuid'.");
}

/**
 * Validate Pantheon drush aliases.
 *
 * @param $aliases
 * @return bool
 */
function terminus_validate_palises($aliases) {
  if (!$aliases) {
    return FALSE;
  }
  // Well, can't exactly PHP lint it...
  $tokens = @token_get_all($aliases);
  if (empty($tokens)) {
    return FALSE;
  }
  if (strpos($tokens[0][1], '<?php') !== 0) {
    return FALSE;
  }
  if (strpos($tokens[1][1], 'Pantheon drush alias file') === FALSE) {
    return FALSE;
  }
  return TRUE;
}

/**
 * Validate list of Pantheon sites.
 *
 * @param $json
 * @return bool
 */
function terminus_validate_sites($json) {
  if (!$json) {
    return FALSE;
  }
  // Make sure it's valid JSON.
  $json_response = @json_decode($json);
  if (json_last_error() != JSON_ERROR_NONE) {
    return FALSE;
  }
  // Not empty.
  if (count((array) $json_response) == 0) {
    return FALSE;
  }
  // Make sure the contents are what we expect.
  foreach ($json_response as $site_uuid => $data) {
    if (!terminus_validate_uuid($site_uuid)) {
      return FALSE;
    }
    if (!isset($data->information)) {
      return FALSE;
    }
    $i = $data->information;
    if (!isset($i->name) || !isset($i->service_level)) {
      return FALSE;
    }
  }
  return TRUE;
}

/**
 * Validate a hostname.
 *
 * @param $hostname
 *   The hostname to be validated.
 *
 * @return bool
 *   Whether the target is a valid hostname.
 */
function terminus_validate_hostname($hostname) {
  return preg_match('/(?=^.{1,254}$)(^(?:(?!\d|-)[a-z0-9\-]{1,63}(?<!-)\.)+(?:[a-z]{2,})$)/i', $hostname);
}