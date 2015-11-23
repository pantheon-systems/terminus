<?php

/**
 * Actions on multiple sites
 *
 */

namespace Terminus\Commands;

use Terminus;
use Terminus\Auth;
use Terminus\Session;
use Terminus\Utils;
use Terminus\Commands\TerminusCommand;
use Terminus\Helpers\Input;
use Terminus\Models\Organization;
use Terminus\Models\Site;
use Terminus\Models\Upstreams;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Models\Collections\Sites;

class SitesCommand extends TerminusCommand {
  public $sites;

  /**
   * Show a list of your sites on Pantheon
   * @package Terminus
   * @version 2.0
   */
  public function __construct() {
    Auth::ensureLogin();
    parent::__construct();
    $this->sites = new Sites();
  }

  /**
   * Show all sites user has access to
   * Note: because of the size of this call, it is cached
   *   and also is the basis for loading individual sites by name
   *
   * [--team]
   * : filter sites you are a team member of
   *
   * [--org=<id>]
   * : filter sites you can access via the organization
   *
   * [--cached]
   * : causes the command to return cached sites list instead of retrieving anew
   *
   * @subcommand list
   * @alias show
   */
  public function index($args, $assoc_args) {
    // Always fetch a fresh list of sites
    if (!isset($assoc_args['cached'])) {
      $this->sites->rebuildCache();
    }
    $sites = $this->sites->all();

    $rows = array();
    foreach ($sites as $site) {
      $memberships = array();
      foreach ($site->get('memberships') as $membership) {
        $memberships[$membership['id']] = $membership['name'];
      }
      $rows[$site->get('id')] = array(
        'name' => $site->get('name'),
        'id' => $site->get('id'),
        'service_level' => $site->get('service_level'),
        'framework' => $site->get('framework'),
        'created' => date('Y-m-d H:i:s', $site->get('created')),
        'memberships' => $memberships,
      );
    } 

    usort($rows, function($row_1, $row_2) {
      return strcasecmp($row_1['name'], $row_2['name']);
    });

    if (isset($assoc_args['team'])) {
      $rows = array_filter($rows, function($site) {
        return in_array('Team', $site['memberships']);
      });
    }

    if (isset($assoc_args['org'])) {
      $org_id = $assoc_args['org'];

      $rows = array_filter($rows, function($site) use ($org_id) {
        return (isset($org_ids[$org_id]) || in_array($org_id, $site['memberships']));
      });
    }

    if (count($rows) == 0) {
      $this->log()->warning('You have no sites.');
    }

    $labels = array('name' => 'Name', 'id', 'ID', 'service_level', 'Service Level', 'framework' => 'Framework', 'created' => 'Created', 'memberships' => 'Memberships');
    $this->output()->outputRecordList($rows, $labels);
  }

  /**
   * Create a new site
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : Name of the site to create (machine-readable)
   *
   * [--name=<name>]
   * : (deprecated) use --site instead
   *
   * [--label=<label>]
   * : Label for the site
   *
   * [--upstream=<upstreamid>]
   * : Specify the upstream upstream to use
   *
   * [--org=<id>]
   * : UUID of organization into which to add this site
   *
   */
  public function create($args, $assoc_args) {
    $options = SitesCommand::getSiteCreateOptions($assoc_args);

    $upstream = Input::upstream($assoc_args, 'upstream');
    $options['upstream_id'] = $upstream->get('id');
    $this->log()->info("Creating new {upstream} installation ... ", array('upstream' => $upstream->get('longname')));

    $workflow = $this->sites->addSite($options);
    $workflow->wait();
    $this->workflowOutput($workflow);

    // Add Site to SitesCache
    $final_task = $workflow->get('final_task');
    $this->sites->addSiteToCache($final_task->site_id);

    Terminus::launchSelf('site', array('info'), array(
      'site' => $options['name'],
    ));

    return true;
  }

  /**
  * Import a new site
  * @package 2.0
  *
  * ## OPTIONS
  *
  * [--url=<url>]
  * : URL of archive to import
  *
  * [--name=<name>]
  * : (deprecated) use --site instead
  *
  * [--site=<site>]
  * : Name of the site to create (machine-readable)
  *
  * [--label=<label>]
  * : Label for the site
  *
  * [--org=<id>]
  * : UUID of organization into which to add this site
  *
  * @subcommand import
  */
  public function import($args, $assoc_args) {
    $options = SitesCommand::getSiteCreateOptions($assoc_args);

    $url = Input::string($assoc_args, 'url', 'URL of archive to import');
    if (!$url) {
      $this->logger->error('Please enter a URL.');
    }

    try {
      //If the site does not yet exist, it will throw an error.
      $site = $this->sites->get($options['name']);
      $this->logger->error(
        sprintf('A site named %s already exists.', $options['name'])
      );
      exit;
    } catch (\Exception $e) {
      //Creating a new site
      $workflow = $this->sites->addSite($options);
      $workflow->wait();
      $this->workflowOutput($workflow);

      //Add site to SitesCache
      $final_task = $workflow->get('final_task');
      $site = $this->sites->addSiteToCache($final_task->site_id);
      sleep(10); //Avoid false site-DNE errors
    }

    $workflow = $site->import($url);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * A helper function for getting/prompting for the site create options.
   *
   * @param [array] $assoc_args Arguments from command
   * @return [array]
   */
  static function getSiteCreateOptions($assoc_args) {
    $options = array();
    $options['label'] = Input::string($assoc_args, 'label', 'Human-readable label for the site');
    $suggested_name = Utils\sanitizeName($options['label']);

    if (array_key_exists('name', $assoc_args)) {
      // Deprecated but kept for backwards compatibility
      $options['name'] = $assoc_args['name'];
    } elseif (array_key_exists('site', $assoc_args)) {
      $options['name'] = $assoc_args['site'];
    } elseif (isset($_SERVER['TERMINUS_SITE'])) {
      $options['name'] = $_SERVER['TERMINUS_SITE'];
    } else {
      $options['name'] = Input::string(
        $assoc_args,
        'site',
        "Machine name of the site; used as part of the default URL (if left blank will be $suggested_name)",
        $suggested_name
      );
    }
    if (isset($assoc_args['org'])) {
      $options['organization_id'] = Input::orgid($assoc_args, 'org', false);
    }
    return $options;
  }

  /**
   * Print and save drush aliases
   *
   * ## OPTIONS
   *
   * [--print]
   * : print aliases to screen
   *
   * [--location=<location>]
   * : Specify the the full path, including the filename, to the alias file you wish to create.
   *   Without this option a default of '~/.drush/pantheon.aliases.drushrc.php' will be used.
   *
   */
  public function aliases($args, $assoc_args) {
    $user = new User(new stdClass(), array());
    $print = Input::optional('print', $assoc_args, false);
    $location = Input::optional('location', $assoc_args, getenv("HOME").'/.drush/pantheon.aliases.drushrc.php');

    // Cannot provide just a directory
    if (is_dir($location)) {
      $this->log()->error("Please provide a full path with filename, e.g. %s/pantheon.aliases.drushrc.php", $location);
      exit(1);
    }

    $file_exists = file_exists($location);

    // Create the directory if it doesn't yet exist
    $dirname = dirname($location);
    if (!is_dir($dirname)) {
      mkdir($dirname, 0700, true);
    }

    $content = $user->getAliases();
    $h = fopen($location, 'w+');
    fwrite($h, $content);
    fclose($h);
    chmod($location, 0700);

    $message = $file_exists ? 'Pantheon aliases updated' : 'Pantheon aliases created';
    $this->log()->info($message);

    if ($print) {
      eval(str_replace(array('<?php', '?>'), '', $content));
      $this->output()->outputDump($aliases);
    }
  }


/**
 * Update all dev sites with an available upstream update.
 *
 * ## OPTIONS
 *
 * [--report]
 * : If set output will contain list of sites and whether they are up-to-date
 *
 * [--upstream=<upstream>]
 * : Specify a specific upstream to check for updating.
 *
 * [--no-updatedb]
 * : Use flag to skip running update.php after the update has applied
 *
 * [--xoption=<theirs|ours>]
 * : Corresponds to git's -X option, set to 'theirs' by default -- https://www.kernel.org/pub/software/scm/git/docs/git-merge.html
 *
 * [--tag=<tag>]
 * : Tag to filter by
 *
 * [--org=<id>]
 * : Only necessary if using --tag. Organization which has tagged the site thusly
 *
 * [--cached]
 * : Set to prevent rebuilding of sites cache
 *
 * @subcommand mass-update
 */
  public function mass_update($args, $assoc_args) {
    // Ensure the sitesCache is up to date
    if (!isset($assoc_args['cached'])) {
      $this->sites->rebuildCache();
    }

    $upstream = Input::optional('upstream', $assoc_args, false);
    $data     = array();
    $report   = Input::optional('report', $assoc_args, false);
    $confirm  = Input::optional('confirm', $assoc_args, false);
    $tag      = Input::optional('tag', $assoc_args, false);

    $org = '';
    if ($tag) {
      $org = Input::orgid($assoc_args, 'org');
    }
    $sites = $this->sites->filterAllByTag($tag, $org);

    // Start status messages.
    if($upstream) $this->log()->info('Looking for sites using '.$upstream.'.');

    foreach($sites as $site) {
      $site->fetch();
      $updates = $site->getUpstreamUpdates();
      if (!isset($updates->behind)) {
        // No updates, go back to start.
        continue;
      }
      // Check for upstream argument and site upstream URL match.
      $siteUpstream = $site->info('upstream');
      if ( $upstream AND isset($siteUpstream->url)) {
        if($siteUpstream->url <> $upstream ) {
          // Uptream doesn't match, go back to start.
          continue;
        }
      }

      if( $updates->behind > 0 ) {
        $data[$site->get('name')] = array('site'=> $site->get('name'), 'status' => "Needs update");
        $env = $site->environments->get('dev');
        if ($env->getConnectionMode() == 'sftp') {
          $this->log()->warning(
            '{site} has available updates, but is in SFTP mode. Switch to Git mode to apply updates.',
            array('site' => $site->get('name'))
          );
          $data[$site->get('name')] = array('site'=> $site->get('name'), 'status' => "Needs update - switch to Git mode");
          continue;
        }
        $updatedb = !Input::optional($assoc_args, 'updatedb', false);
        $xoption = Input::optional($assoc_args, 'xoption', 'theirs');
        if (!$report) {
          $confirmed = Input::yesno("Apply upstream updates to %s ( run update.php:%s, xoption:%s ) ", array($site->get('name'), var_export($updatedb,1), var_export($xoption,1)));
          if(!$confirmed) continue; // User says No, go back to start.

          // Backup the DB so the client can restore if something goes wrong.
          $this->log()->info('Backing up '.$site->get('name').'.');
          $backup = $env->createBackup(array('element'=>'all'));
          // Only continue if the backup was successful.
          if($backup) {
            $this->log()->info("Backup of ".$site->get('name')." created.");
            $this->log()->info('Updating '.$site->get('name').'.');
            // Apply the update, failure here would trigger a guzzle exception so no need to validate success.
            $response = $site->applyUpstreamUpdates($env->get('id'), $updatedb, $xoption);
            $data[$site->get('name')]['status'] = 'Updated';
            $this->log()->info($site->get('name').' is updated.');
          } else {
            $data[$site->get('name')]['status'] = 'Backup failed';
            $this->log()->error('There was a problem backing up '.$site->get('name').'. Update aborted.');
          }
        }
      } else {
        if (isset($assoc_args['report'])) {
          $data[$site->get('name')] = array('site'=> $site->get('name'), 'status' => "Up to date");
        }
      }
    }

    if (!empty($data)) {
      sort($data);
      $this->output()->outputRecordList($data);
    } else {
      $this->log()->info('No sites in need of updating.');
    }
  }
}

Terminus::addCommand( 'sites', 'SitesCommand' );
