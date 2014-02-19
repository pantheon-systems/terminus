<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 * @when before_wp_load
 */
class Sites_Command extends Terminus_Command {
	/**
	 * Show a list of your sites on Pantheon
	 */
	public function show() {
    $sites = $this->terminus_request('user', $this->session->user_uuid, 'sites');
    var_dump($sites);
	}


}

Terminus::add_command( 'sites', 'Sites_Command' );

