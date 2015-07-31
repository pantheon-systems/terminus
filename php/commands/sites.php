<?php
/**
 * Actions on multiple sites
 *
 */
use Terminus\Utils;
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

class Sites_Command extends Terminus_Command {
  /**
   * Show a list of your sites on Pantheon
   * @package Terminus
   * @version 2.0
   */
  public function __construct() {
    parent::__construct();
    Auth::loggedIn();
  }

  /**
   * List Sites in Cache
   *
   * ## OPTIONS
   *
   * [--rebuild]
   * @subcommand cache
   */
  public function cache($args, $assoc_args) {
    $sites_cache = new Terminus\SitesCache();

    if (isset($assoc_args['rebuild'])) {
      $sites_cache->rebuild();
    }

    $sites = $sites_cache->all();

    $data = array();
    foreach ($sites as $name => $id) {
      $data[] = array(
        'name' => $name,
        'id' => $id
      );
    }

    $this->handleDisplay($data, $args);
  }

  /**
   * Show sites
   *
   * ## OPTIONS
   *
   * @subcommand list
   * @alias show
   */
  public function show($args, $assoc_args) {
    $sites = SiteFactory::instance();

    if (count($sites) == 0) {
      Terminus::log("You have no sites.");
      exit(0);
    }

    $data = array();
    foreach ($sites as $site) {
      $report = array(
        'name' => $site->getName(),
      );

      $fields = Input::optional('fields', $assoc_args, 'name,framework,service_level,id');
      $filter = Input::optional('filter', $assoc_args, false);
      if ($filter) {
        if (!strpos($filter,":")) Terminus::error("Improperly formatted filter");
        $filter = explode(':',$filter);
        list($filter_field, $filter_value) = $filter;
        if (!preg_match("#".preg_quote($filter_value)."#", $site->info($filter_field))) {
          // skip rows not matching our filter
          continue;
        }
      }
      if ($fields) {
        $fields = explode(',',$fields);
        foreach ($fields as $field) {
          $report[$field] = $site->info($field);
        }
      } else {
        $info = $site->info();
        foreach ($info as $key=>$value) {
          $report[$key] = $value;
        }
      }

      $data[] = $report;
    }

    $this->handleDisplay($data);
    return $data;
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
   * [--org=<org>]
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

    // Add Name->ID mapping to SitesCache
    $site_id = $workflow->status()->final_task->site_id;
    $sites_cache = new Terminus\SitesCache();
    $sites_cache->add(array($options['name'] => $site_id));

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
  * [--org=<org>]
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
   * Delete a site from pantheon
   *
   * ## OPTIONS
   * --site=<site>
   * : Id of the site you want to delete
   *
   * [--all]
   * : Just kidding ... we won't let you do that.
   *
   * [--force]
   * : to skip the confirmations
   *
   */
  function delete($args, $assoc_args) {
      $site_to_delete = SiteFactory::instance(@$assoc_args['site']);
      if (!$site_to_delete) {
        foreach( SiteFactory::instance() as $id => $site ) {
          $site->id = $id;
          $sites[] = $site;
          $menu[] = $site->information->name;
        }
        $index = Terminus::menu( $menu, null, "Select a site to delete" );
        $site_to_delete = $sites[$index];
      }

      if (!isset($assoc_args['force']) AND !Terminus::get_config('yes')) {
        // if the force option isn't used we'll ask you some annoying questions
        Terminus::confirm( sprintf( "Are you sure you want to delete %s?", $site_to_delete->information->name ));
        Terminus::confirm( "Are you really sure?" );
      }
      Terminus::line( sprintf( "Deleting %s ...", $site_to_delete->information->name ) );
      $response = \Terminus_Command::request( 'sites', $site_to_delete->id, '', 'DELETE' );

      Terminus::success("Deleted %s!", $site_to_delete->information->name);
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
   * : specify the location of the alias file, default it ~/.drush/pantheon.drushrc.php
   *
   */
  public function aliases($args, $assoc_args) {
    $user = new User();
    $print = Input::optional('print', $assoc_args, false);
    $json = \Terminus::get_config('json');
    $location = Input::optional('location', $assoc_args, getenv("HOME").'/.drush/pantheon.aliases.drushrc.php');
    $message = "Pantheon aliases updated.";
    if (!file_exists($location)) {
      $message = "Pantheon aliases created.";
    }
    $content = $user->getAliases();
    $h = fopen($location, 'w+');
    fwrite($h, $content);
    fclose($h);
    chmod($location, 0777);
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
    $sites = SiteFactory::instance();
    $env = 'dev';
    $upstream = Input::optional('upstream', $assoc_args, false);
    $data = array();
    $report = Input::optional('report', $assoc_args, false);
    $confirm = Input::optional('confirm', $assoc_args, false);

    // Start status messages.
    if($upstream) Terminus::line('Looking for sites using '.$upstream.'.');

    foreach( $sites as $site ) {

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
        $noupdatedb = Input::optional($assoc_args, 'updatedb', false);
        $update = $noupdatedb ? false : true;
        $xoption = Input::optional($assoc_args, 'xoption', 'theirs');
        if (!$report) {
          $confirmed = Input::yesno("Apply upstream updatefs to %s ( run update.php:%s, xoption:%s ) ", array($site->getName(), var_export($update,1), var_export($xoption,1)));
          if( !$confirmed ) continue; // Suer says No, go back to start.

          // Backup the DB so the client can restore if something goes wrong.
          Terminus::line('Backing up '.$site->getName().'.');
          $backup = $site->environment('dev')->createBackup(array('element'=>'all'));
          // Only continue if the backup was successful.
          if($backup) {
            Terminus::success("Backup of ".$site->getName()." created.");
            Terminus::line('Updating '.$site->getName().'.');
            // Apply the update, failure here would trigger a guzzle exception so no need to validate success.
            $response = $site->applyUpstreamUpdates($env, $update, $xoption);
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
