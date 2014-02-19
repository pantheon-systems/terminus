<?php
/**
 * Authenticate to Pantheon and store a local secret token.
 *
 * @when before_wp_load
 */
class Auth_Command extends WP_CLI_Command {

	/**
	 * Log yourself out and remove the secret session key.
	 */
	public function login( $args ) {
      if (empty($args)) {
        $email = \cli\prompt( "Your email address?", NULL );
      }
      else {
        $email = $args[0];
      }

      if ( \WP_CLI\Utils\is_valid_email( $email ) ) {
        exec("stty -echo");
        $password = \cli\prompt( "Your dashboard password (input will not be shown)" );
        exec("stty echo");
        \cli\line();
        \cli\line( "Logging in as $email" );
        $data = \WP_CLI\Login\auth( $email, $password );
        if ( $data != FALSE ) {
          \cli\line( "Success!" );
          $this->cache->put_data('session', $data);
        }
        else {
          \cli\line( "Login Failed/" );
        }
      }
      else {
        \cli\line( "Error: invalid email address" );
      }
	}

	/**
	 * Log yourself out and remove the secret session key.
	 */
	public function logout() {
		\cli\line( "Logging out of to Pantheon." );
		$this->cache->remove('session');
	}

	/**
	 * Find out what user you are logged in as.
	 */
	public function whoami() {
		if ($this->session) {
		  \cli\line( "You are authenticated as ". $this->session->email );
		}
		else {
		  \cli\line( "You are not logged in." );
		}
	}

}

WP_CLI::add_command( 'auth', 'Auth_Command' );

