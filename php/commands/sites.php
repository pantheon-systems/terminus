<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 */
class Sites_Command extends Terminus_Command {
	/**
	 * Show a list of your sites on Pantheon
	 *
   *  ## OPTIONS
   *
	 * [--nocache]
	 * : Get a fresh list of sites from the server side.
	 */
	public function show( $args, $assoc_args ) {
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
	 * Helper code to grab sites and manage local cache.
	 */
	private function fetch_sites( $nocache = false ) {
	  $sites = $this->cache->get_data('sites');
	  if (!$sites || $nocache) {
	    Terminus::log( 'Fetching site list from Pantheon' );
      $request = $this->terminus_request( 'user',
                                        $this->session->user_uuid,
                                        'sites',
                                        'GET',
                                        Array('hydrated' => true));
      # TODO: handle errors well.
      $sites = $request['data'];
      $this->cache->put_data( 'sites', $sites );
    }
    return $sites;
	}
}

Terminus::add_command( 'sites', 'Sites_Command' );

