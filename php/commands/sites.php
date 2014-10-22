<?php
/**
 * Actions on multiple sites
 *
 */
use Terminus\Products;

class Sites_Command extends Terminus_Command {
  /**
   * Show a list of your sites on Pantheon
   * @package Terminus
   * @version 1.5

   *  ## OPTIONS
   *
   * [--nocache]
   * : Get a fresh list of sites from the server side.
   */
  public function show($args, $assoc_args) {
    $sites = $this->fetch_sites( @$assoc_args['nocache'] );
    $headers = Array('Site', 'Framework', 'Service Level', 'UUID');
    $rows = Array();
    foreach($sites as $id => $site) {
      $rows[] = Array(
        $site->information->name,
        isset($site->information->framework) ? $site->information->framework : '',
        $site->information->service_level,
        $id
      );
    }

    $table = new \cli\Table();
    $table->setHeaders($headers);
    $table->setRows($rows);
    $table->display();
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
    if(empty($this->session)) {
      Terminus::error("Please login first with `terminus auth login`");
    }
    $sites = $this->fetch_sites(isset($assoc_args['nocache']));
    $data = array();
    // @TODO clean this up and move to separate method
    $data['label'] = @$assoc_args['label'] ?: Terminus::prompt("Human readable label for the site");
    $data['name'] = @$assoc_args['name'] ?: Terminus::prompt("Machine name of the site; used as part of the default URL [ i.e. ".$this->sanitizeName( $data['label'] )."]");
    require_once __DIR__.'/products.php';
    $product = Terminus::menu( Products_Command::selectList() );
    $product = Products_Command::getByIndex( $product);
    Terminus::line( sprintf( "Creating new %s installation ... ", $product['longname'] ) );
    $data['product'] = $product['id'];
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json'));
    $response = $this->terminus_request( "user", $this->session->user_uuid, "sites", 'POST', $options );

    // if we made it this far we need to query the work flow to wait for response
    $site = $response['data'];
    $workflow_id = $site->id;
    $result = $this->waitOnWorkFlow( 'sites', $site->site_id, $workflow_id );
    if( $result ) {
      Terminus::success("Pow! You created a new site!");
      Terminus::launch_self('sites',array('show'),array('nocache'));
    }
  }

  /**
   * Delete site
   * [<siteid>]
   *  : Id of the site you want to delete

   * [--all]
   *  : Just kidding ... we won't let you do that.
   */
  function delete($args, $assoc_args) {
      if( !@$args['siteid'] ) {
        $sites = $menu = array();
        foreach( $this->fetch_sites(true) as $id => $site ) {
          $site->id = $id;
          $sites[] = $site;
          $menu[] = $site->information->name;
        }
        $index = Terminus::menu( $menu, null, "Select a site to delete" );
        $site_to_delete = $sites[$index];
      }

      Terminus::confirm( sprintf( "Are you sure you want to delete %s?", $site_to_delete->information->name ));
      Terminus::confirm( "Are you really sure?" );
      Terminus::line( sprintf( "Deleting %s ...", $site_to_delete->information->name ) );

      $response = $this->terminus_request( 'sites', $site_to_delete->id, '', 'DELETE' );

      Terminus::launch_self("sites",array('show'),array('nocache'=>1));
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
