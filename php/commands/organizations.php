<?php
use \Terminus\User;
use \Terminus\Utils;
use \Terminus\Auth;
use \Terminus\SiteFactory;
use \Terminus\Organization;
use \Terminus\Helper\Input;
use \Guzzle\Http\Client;
use \Terminus\Loggers\Regular as Logger;

class Organizations_Command extends Terminus_Command {

  public function __construct() {
    parent::__construct();
  }

  /**
   * API call to get a user's organizations.
   *
   * @subcommand list
   *
  */
  public function all($args, $assoc_args) {
     $user = new User();
     $data = array();
     foreach ( $user->organizations() as $org_id => $org) {
       $data[] = array(
         'name' => $org->name,
         'id' => $org_id,
       );
     }

     $this->handleDisplay($data);
  }

  /**
   * List an organizations sites
   *
   * @subcommand sites
   *
   * ## Options
   *
   * [--org=<org>]
   * : Organization name or Id
   * [--add=<site>]
   * : Site to add to organization
   * [--remove=<site>]
   * : Site to remove from organization
   *
   */
  public function sites($args, $assoc_args) {
    $orgs = array();
    $user = new User();

    foreach ($user->organizations() as $id => $org) {
      $orgs[$id] = $org->name;
    }

    if (!isset($assoc_args['org']) OR empty($assoc_args['org'])) {
      $selected_org = Terminus::menu($orgs,false,"Choose an organization");
    } else {
      $selected_org = $assoc_args['org'];
    }

    $org = new Organization($selected_org);

    if (isset($assoc_args['add'])) {
        $add = SiteFactory::instance(Input::site($assoc_args,'add'));

        Terminus::confirm("Are you sure you want to add %s to %s ?", $assoc_args, array($add->getName(), $org->name));
        $org->addSite($add);
        Terminus::success("Added site!");
        return true;
    }

    if (isset($assoc_args['remove'])) {
      $remove = SiteFactory::instance(Input::site($assoc_args,'remove'));

      Terminus::confirm("Are you sure you want to remove %s to %s ?", $assoc_args, array($remove->getName(), $org->name));
      $org->removeSite($remove);
      Terminus::success("Removed site!");
      return true;
    }

    $sites = $user->sites($selected_org);
    $data = array();
    foreach ($sites as $site) {
      $data[] = array(
        'name' => $site->name,
        'service level' => $site->service_level,
        'framework' => $site->framework,
        'created' => date('Y-m-d H:i:s', $site->created),
      );
    }
    $this->handleDisplay($data);
  }

}
Terminus::add_command( 'organizations', 'Organizations_Command' );
