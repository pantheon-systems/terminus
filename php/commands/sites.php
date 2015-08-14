<?php
/**
 * Actions on multiple sites
 *
 */
use Terminus\Utils;
use Terminus\Organization;
use Terminus\Products;
use Terminus\Session;
use Terminus\SitesCache;
use Terminus\Site;
use Terminus\SiteFactory;
use Terminus\Auth;
use Terminus\Helpers\Input;
use Terminus\User;
use Symfony\Component\Finder\SplFileInfo;
use Terminus\Loggers\Regular as Logger;
use Terminus\Workflow;

class Sites_Command extends TerminusCommand {
  public $sitesCache;

  /**
   * Show a list of your sites on Pantheon
   * @package Terminus
   * @version 2.0
   */
  public function __construct() {
    parent::__construct();
    Auth::loggedIn();

    $this->sitesCache = new SitesCache();
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
   * @subcommand list
   * @alias show
   */
  public function index($args, $assoc_args) {
    // Always fetch a fresh list of sites
    $this->sitesCache->rebuild();
    $cached_sites = $this->sitesCache->all();

    $rows = array_map(function($cached_site) {
      return array(
        'name' => $cached_site['name'],
        'id' => $cached_site['id'],
        'service_level' => $cached_site['service_level'],
        'framework' => $cached_site['framework'],
        'created' => date('Y-m-d H:i:s', $cached_site['created']),
        'memberships' => array_map(function($membership) {
          return $membership['name'];
        }, array_values($cached_site['memberships']))
      );
    }, array_values($cached_sites));

    if (isset($assoc_args['team'])) {
      $rows = array_filter($rows, function($site) {
        return in_array('Team', $site['memberships']);
      });
    }

    if (isset($assoc_args['org'])) {
      $org_id = $assoc_args['org'];

      $rows = array_filter($rows, function($site) use ($org_id) {
        $org_ids = array_keys($site['memberships']);
        return in_array($org_id, $org_ids);
      });
    }

    if (count($rows) == 0) {
      Terminus::log("You have no sites.");
      exit(0);
    }

    $this->handleDisplay($rows);
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
   * [--product=<productid>]
   * : Specify the upstream product to use
   *
   * [--import=<url>]
   * : A url to import a valid archive
   *
   * [--org=<id>]
   * : UUID of organization into which to add this site
   *
   */
  public function create($args, $assoc_args) {
    $options = array();
    $options['label'] = Input::string($assoc_args, 'label', "Human readable label for the site");
    $suggested_name = Utils\sanitize_name( $options['label'] );

    if (array_key_exists('name', $assoc_args)) {
      // Deprecated but kept for backwards compatibility
      $options['name'] = $assoc_args['name'];
    } elseif (array_key_exists('site', $assoc_args)) {
      $options['name'] = $assoc_args['site'];
    } else {
      $options['name'] = Input::string($assoc_args, 'site', "Machine name of the site; used as part of the default URL (if left blank will be $suggested_name)", $suggested_name);
    }
    if ($org_id = Input::orgid($assoc_args, 'org', false)) {
      $options['organization_id'] = $org_id;
    }
    if (!isset($assoc_args['import'])) {
      $product = Input::product($assoc_args, 'product');
      $options['product_id'] = $product['id'];
      Terminus::line(sprintf("Creating new %s installation ... ", $product['longname']));
    }

    $workflow = Site::create($options);
    $workflow->wait();
    Terminus::success("Pow! You created a new site!");

    // Add Site to SitesCache
    $site_id = $workflow->attributes->final_task->site_id;
    $site = new Site($site_id);
    $site->fetch();

    $cache_membership = array(
      'id' => $site_id,
      'name' => $options['name'],
      'created' => $site->attributes->created,
      'service_level' => $site->attributes->service_level,
      'framework' => $site->attributes->framework,
    );

    if ($org_id) {
      $org = new Organization($org_id);
      $cache_membership['membership'] = array(
        'id' => $org_id,
        'name' => $org->profile->name,
        'type' => 'organization'
      );
    } else {
      $user_id = Session::getValue('user_uuid');
      $cache_membership['membership'] = array(
        'id' => $user_id,
        'name' => 'Team',
        'type' => 'team'
      );
    }
    $sites_cache = new Terminus\SitesCache();
    $sites_cache->add($cache_membership);

    if (isset($assoc_args['import'])) {
      sleep(10); //To stop erroenous site-DNE errors
      Terminus::launch_self('site', array('import'), array(
        'url' => $assoc_args['import'],
        'site' => $options['name'],
        'element' => 'all'
      ));
    } else {
      Terminus::launch_self('site', array('info'), array(
        'site' => $options['name'],
      ));
    }

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
  * @subcommand create-from-import
  */
  public function import($args, $assoc_args) {
    $url = Input::string($assoc_args, 'url', "URL of archive to import");
    if (!$url) {
      Terminus::error("Please enter a URL.");
    }
    $assoc_args['import'] = $url;
    unset($assoc_args['url']);

    Terminus::launch_self('sites', array('create'), $assoc_args);
  }

  /**
   * [Deprecated] Delete a site from pantheon; use `site delete` instead
   *
   * ## OPTIONS
   * [--site=<site>]
   * : ID of the site you want to delete
   *
   * [--force]
   * : to skip the confirmations
   */
  function delete($args, $assoc_args) {
    Terminus::launch_self('site', array('delete'), $assoc_args);
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
   *   Without this option a default of '~/.drush/pantheon.drushrc.php' will be used.
   *
   */
  public function aliases($args, $assoc_args) {
    $user = new User();
    $print = Input::optional('print', $assoc_args, false);
    $json = \Terminus::get_config('json');
    $location = Input::optional('location', $assoc_args, getenv("HOME").'/.drush/pantheon.aliases.drushrc.php');

    // Cannot provide just a directory
    if (is_dir($location)) {
      \Terminus::error("Please provide a full path with filename, e.g. %s/pantheon.aliases.drushrc.php", $location);
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
    Logger::coloredOutput("%2%K$message%n");

    if ($json) {
      include $location;
      print \Terminus\Utils\json_dump($aliases);
    } elseif ($print) {
      print $content;
    }
  }


/**
 * Update alls dev sites with an available upstream update.
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
 * @subcommand mass-update
 */
  public function mass_update($args, $assoc_args) {
    // Ensure the sitesCache is up to date
    $this->sitesCache->rebuild();
    $sites_cache = $this->sitesCache->all();

    $env = 'dev';
    $upstream = Input::optional('upstream', $assoc_args, false);
    $data = array();
    $report = Input::optional('report', $assoc_args, false);
    $confirm = Input::optional('confirm', $assoc_args, false);

    // Start status messages.
    if($upstream) Terminus::line('Looking for sites using '.$upstream.'.');

    foreach($sites_cache as $site_cache ) {
      $site = new Site($site_cache['id']);
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
        $data[$site->getName()] = array('site'=> $site->getName(), 'status' => "Needs update");
        $updatedb = !Input::optional($assoc_args, 'updatedb', false);
        $xoption = Input::optional($assoc_args, 'xoption', 'theirs');
        if (!$report) {
          $confirmed = Input::yesno("Apply upstream updates to %s ( run update.php:%s, xoption:%s ) ", array($site->getName(), var_export($update,1), var_export($xoption,1)));
          if(!$confirmed) continue; // User says No, go back to start.

          // Backup the DB so the client can restore if something goes wrong.
          Terminus::line('Backing up '.$site->getName().'.');
          $backup = $site->environment('dev')->createBackup(array('element'=>'all'));
          // Only continue if the backup was successful.
          if($backup) {
            Terminus::success("Backup of ".$site->getName()." created.");
            Terminus::line('Updating '.$site->getName().'.');
            // Apply the update, failure here would trigger a guzzle exception so no need to validate success.
            $response = $site->applyUpstreamUpdates($env, $updatedb, $xoption);
            $data[$site->getName()]['status'] = 'Updated';
            Terminus::success($site->getName().' is updated.');
          } else {
            $data[$site->getName()]['status'] = 'Backup failed';
            Terminus::error('There was a problem backing up '.$site->getName().'. Update aborted.');
          }
        }
      } else {
        if (isset($assoc_args['report'])) {
          $data[$site->getName()] = array('site'=> $site->getName(), 'status' => "Up to date");
        }
      }
    }

    if (!empty($data)) {
      sort($data);
      $this->handleDisplay($data);
    } else {
      Terminus::line('No sites in need up updating.');
    }
  }
}

Terminus::add_command( 'sites', 'Sites_Command' );
