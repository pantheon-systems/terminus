<?php

use \Terminus\Dispatcher,
  \Terminus\Utils,
  \Terminus\CommandWithSSH;


class Drush_Command extends CommandWithSSH {

  /**
   * Invoke `drush` commands on a Pantheon development site
   *
   * --site=<site>
   * : The name of your site
   *
   * [--env=<environment>]
   * : Your dev or multidev environment. Default: dev
   */
  function __invoke( $args, $assoc_args ) {
    $site_name = $assoc_args['site'];
    if (isset($assoc_args['environment'])) {
      $environment = $assoc_args['environment'];
    }
    else {
      $environment = 'dev';
    }

    $sites = $this->terminus_request('user', $this->session->user_uuid, 'sites')['data'];

    $site_names = Array();
    foreach($sites as $id => $site) {
      $site_names[$site->information->name] = $id;
    }

    if (!isset($site_names[$site_name])) {
      Terminus::error("The site named '$site_name' does not exist. Run `terminus sites show` for a list of sites.");
    }

    $site_id = $site_names[$site_name];

    $site = $this->terminus_request('site', $site_id, 'state')['data'];

    if (!isset($site->environments->$environment)) {
      Terminus::error("The '$environment' environment does not exist.");
    }

    # see https://github.com/pantheon-systems/titan-mt/blob/master/dashboardng/app/workshops/site/models/environment.coffee
    $server = Array(
      'user' => "$environment.$site_id",
      'host' => "appserver.$environment.$site_id.drush.in",
      'port' => '2222'
    );

    $this->send_command($server, 'drush', $args, $assoc_args );
  }

}

Terminus::add_command( 'drush', 'Drush_Command' );
