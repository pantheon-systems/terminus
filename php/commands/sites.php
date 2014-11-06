<?php
/**
 * Actions on multiple sites
 *
 */
use Terminus\Products;
use Terminus\Session;
use Terminus\SiteFactory;
use Terminus\Auth;

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
   *  ## OPTIONS
   *
   * [--nocache]
   * : Get a fresh list of sites from the server side.
   * [--bash]
   * : Get bash friendly output
   * [--json]
   * : Json output
   */
  public function show($args, $assoc_args) {
    $sites = SiteFactory::instance();
    $toReturn = array();
    $toReturn['sites'] = $sites;
    $toReturn['data'] = array();
    foreach($sites as $id => $site) {
      $toReturn['data'][] = array(
        'Site' => $site->information->name,
        'Framwork' => isset($site->information->framework) ? $site->information->framework : '',
        'Service Level' => $site->information->service_level,
        'UUID' => $id
      );
    }
    if (@$assoc_args['bash']) {
      echo \Terminus\Utils\bash_out((array) $toReturn['data']);
    } else {
      $this->_constructTableForResponse($toReturn['data']);
    }
    return $toReturn;
  }

  /**
   * Create a new site
   * @package 2.0
   *
   * ## OPTIONS
   *
   * [--product]
   * : Specify the product to create
   * [--name]
   * : Name of the site to create (machine-readable)
   * [--label]
   * : Label for the site
   */
  public function create($args, $assoc_args) {
    $sites = $this->fetch_sites(isset($assoc_args['nocache']));
    $data = array();
    // @TODO clean this up and move to separate method
    $data['label'] = @$assoc_args['label'] ?: Terminus::prompt("Human readable label for the site");
    $data['name'] = @$assoc_args['name'] ?: Terminus::prompt("Machine name of the site; used as part of the default URL [ i.e. ".$this->sanitizeName( $data['label'] )."]");
    require_once __DIR__.'/products.php';
    if (isset($assoc_args['product'])) {
      $product = Products_Command::getById($assoc_args['product']);
    } else {
      $product = Terminus::menu( Products_Command::selectList() );
      $product = Products_Command::getByIndex($product);
    }
    Terminus::line( sprintf( "Creating new %s installation ... ", $product['longname'] ) );
    $data['product'] = $product['id'];
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
    $response = \Terminus_Command::request( "user", Session::getValue('user_uuid'), "sites", 'POST', $options );
    // if we made it this far we need to query the work flow to wait for response
    $site = $response['data'];
    $workflow_id = $site->id;
    $result = $this->waitOnWorkFlow( 'sites', $site->site_id, $workflow_id );
    if( $result ) {
      Terminus::success("Pow! You created a new site!");
      $this->cache->flush('sites');
    }
    return true;
  }

  /**
   * Delete site
   * --site=<site>
   *  : Id of the site you want to delete

   * [--all]
   *  : Just kidding ... we won't let you do that.
   *
   * [--force]
   *  : to skip the confirmations
   */
  function delete($args, $assoc_args) {
      $site_to_delete = $this->getIdFromName(@$assoc_args['site']);
      if (!$site_to_delete) {
        foreach( $this->fetch_sites(true) as $id => $site ) {
          $site->id = $id;
          $sites[] = $site;
          $menu[] = $site->information->name;
        }
        $index = Terminus::menu( $menu, null, "Select a site to delete" );
        $site_to_delete = $sites[$index];
      }

      if (!isset($assoc_args['force'])) {
        // if the force option isn't used we'll ask you some annoying questions
        Terminus::confirm( sprintf( "Are you sure you want to delete %s?", $site_to_delete->information->name ));
        Terminus::confirm( "Are you really sure?" );
      }
      Terminus::line( sprintf( "Deleting %s ...", $site_to_delete->information->name ) );

      $response = \Terminus_Command::request( 'sites', $site_to_delete->id, '', 'DELETE' );

      Terminus::launch_self("sites",array('show'),array('nocache'=>1));
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
  /**
   * Sanitize the site name field
   * @package 2.0
   */
  private function sanitizeName( $str ) {
    $name = preg_replace("#[^A-Za-z0-9]#","", $str);
    $name = strtolower($name);
    return $name;
  }
}

Terminus::add_command( 'sites', 'Sites_Command' );
