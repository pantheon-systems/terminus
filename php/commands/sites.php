<?php
/**
 * Actions on multiple sites
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
   *
   * [--json]
   * : export list to JSON
   *
   */
  public function show( $args, $assoc_args ) {
    // when invoked, always get a fresh list
    $sites = $this->fetch_sites(true);
    if (array_key_exists("json", $assoc_args)) {
      echo \Terminus\Utils\json_dump($sites);
    } else {
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
  }

}

Terminus::add_command( 'sites', 'Sites_Command' );

