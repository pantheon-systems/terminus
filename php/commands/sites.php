<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 */
class Sites_Command extends Terminus_Command {
	/**
	 * Show a list of your sites on Pantheon
	 */
	public function show( $arguments ) {
    $sites = $this->terminus_request('user', $this->session->user_uuid, 'sites', 'GET', Array('hydrated' => true))['data'];

    $headers = Array('Site', 'Framework', 'Service Level', 'UUID');
    $rows = Array();
    var_dump($arguments);
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
}

Terminus::add_command( 'sites', 'Sites_Command' );

