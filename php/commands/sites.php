<?php
/**
 * Actions on multiple sites
 *
 */
class Sites_Command extends Terminus_Command {
  /**
   * Show a list of your sites on Pantheon
   * @package 2.0

   *  ## OPTIONS
   *
   * [--nocache]
   * : Get a fresh list of sites from the server side.
   */
  public function show($args, $assoc_args) {
    $sites = $this->fetch_sites(isset($assoc_args['nocache']));
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
    print_r($this->session);
    $data = array();
    // @TODO
    $data['label'] = @$assoc_args['label'] ?: Terminus::prompt("Human readable label for the site");
    $data['name'] = @$assoc_args['name'] ?: Terminus::prompt("Machine name of the site; used as part of the default URL [ i.e. ".$this->sanitizeName( $data['label'] )."]");
    $data['product'] = 'e8fe8550-1ab9-4964-8838-2b9abdccf4bf';
    $response = $this->terminus_request( "user", $this->session->user_uuid, "sites", 'POST', $data );
    print_r($response);
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

  /**
   * Get list of products from pantheon
   * ## OPTIONS
   *
   */
  public function products() {
    if( !$products = $this->cache->get_data('products') ) {
      $response = $this->terminus_request("products", "public", false, "GET");
      $products = array();
      $keys_to_show = array('longname','framework','type','category');
      // we'll use this to sort the list later
      $sort = array();
      foreach( (array) $response['data'] as $id=>$details ) {
        $sort[] = $details->attributes->shortname;
        $row = array();
        $row['id'] = $id;
        foreach( $keys_to_show as $key ) {
          $row[$key] = @$details->attributes->$key;
        }
        array_push($products, $row);
      }
      array_multisort( $sort, SORT_ASC, SORT_REGULAR, $products);
      $this->cache->put_data('products', $products);
    }
    $this->_constructTableForResponse( $products );
    return (array) $products;
  }

}

Terminus::add_command( 'sites', 'Sites_Command' );
