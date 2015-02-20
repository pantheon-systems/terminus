<?php
/**
 * Actions on multiple sites
 *
 */
use Terminus\Utils;
use Terminus\Products;
use Terminus\Session;
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
   * Show sites
   *
   * ## OPTIONS
   *
   * @subcommand list
   * @alias show
   */
  public function show($args, $assoc_args) {
    $sites = SiteFactory::instance();
    $toReturn = array();
    $toReturn['sites'] = $sites;
    $toReturn['data'] = array();
    foreach($sites as $id => $site) {
      $toReturn['data'][] = array(
        'Site' => $site->information->name,
        'Framework' => isset($site->information->framework) ? $site->information->framework : '',
        'Service Level' => $site->information->service_level,
        'UUID' => $id
      );
    }

    $this->handleDisplay($toReturn['data']);
    return $toReturn;
  }

  /**
   * Create a new site
   *
   * ## OPTIONS
   *
   * [--product=<productid>]
   * : Specify the product to create
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
   * : UUID of organization to add this site to
   *
   * [--import=<url>]
   * : A url to import a valid archive from
   */
  public function create($args, $assoc_args) {
    $sites = SiteFactory::instance();

    // setup data
    $data = array();
    $data['label'] = Input::string($assoc_args, 'label', "Human readable label for the site");
    $slug = Utils\sanitize_name( $data['label'] );
    // this ugly logic is temporarily if to handle the deprecated --name flag and preserve backward compatibility. it can be removed in the next major release. 
    if (array_key_exists('name',$assoc_args)) {
      $data['site_name'] = $assoc_args['name'];
    } elseif (array_key_exists('site',$assoc_args)) {    
      $data['site_name'] = $assoc_args['site'];
    } else {
      $data['site_name'] = Input::string($assoc_args, 'site', "Machine name of the site; used as part of the default URL [ if left blank will be $slug]", $slug);
    }
    if ($orgid = Input::orgid($assoc_args,'org', false)) {
      $data['organization_id'] = $orgid;
    }
    $product = Input::product($assoc_args,'product');
    $data['deploy_product'] = array('product_id' => $product['id']);
    Terminus::line(sprintf("Creating new %s installation ... ", $product['longname']));
    $params = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );

    // run the workflow
    $workflow = Workflow::createWorkflow('create_site','users', new User());
    $workflow->setMethod('POST');
    $workflow->setParams($data);
    $workflow->start();
    $workflow->refresh();
    $details = $workflow->status();
    if ($details->result !== 'failed' AND $details->result !== 'aborted') {
      Terminus\Loggers\Regular::coloredOutput('%G'.vsprintf('New "site" %s now building with "UUID" %s', array($details->waiting_for_task->params->site_name, $details->waiting_for_task->params->site_id))); 
    }
    $workflow->wait();
    Terminus::success("Pow! You created a new site!");
    $this->cache->flush(null,'session');

    if (isset($assoc_args['import'])) {
      Terminus::launch_self('site', array('import'), array('url'=>$assoc_args['import'], 'site'=>$data['name'], 'nocache' => True));
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
  * : Url of archive to import
  *
  * [--name=<name>]
  * : Name of the site to create (machine-readable)
  *
  * [--label=<label>]
  * : Label for the site
  *
  * [--org=<org>]
  * : UUID of organization to add this site to
  *
  * @subcommand create-from-import
  */
  public function import($args, $assoc_args) {
    $url = Input::string($assoc_args, 'url', "Url of archive to import");
    $label = Input::string($assoc_args, 'label', "Human readable label for the site");
    $slug = Utils\sanitize_name( $label );
    $name = Input::string($assoc_args, 'name', "Machine name of the site; used as part of the default URL [ if left blank will be $slug]");
    $name = $name ? $name : $slug;
    $organization = Terminus::menu(Input::orglist(), false, "Choose organization");
    if (!$url) {
      Terminus::error("Please enter a url.");
    }
    Terminus::launch_self('sites', array('create'), array(
      'label' => $label,
      'name'  => $name,
      'org'   => $organization,
    ));
    Terminus::launch_self('site', array('import'), array('url'=>$url, 'site'=>$name, 'nocache' => True));
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

  private function getIdFromName($name) {
    $sites = $menu = array();
    foreach( $this->fetch_sites(true) as $id => $site ) {
       if ( $site->information->name == $name ) {
         $site->id = $id;
         return $site;
       }
    }
    return false;
  }
}

Terminus::add_command( 'sites', 'Sites_Command' );
