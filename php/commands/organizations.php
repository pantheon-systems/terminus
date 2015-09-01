<?php

use \Terminus\Models\User;
use \Terminus\Utils;
use \Terminus\Auth;
use \Terminus\SiteFactory;
use \Terminus\Organization;
use \Terminus\Helpers\Input;
use \Guzzle\Http\Client;
use \Terminus\Loggers\Regular as Logger;

/**
 * Show information for your Pantheon organizations
 *
 */
class Organizations_Command extends TerminusCommand {

  public function __construct() {
    parent::__construct();
  }

  /**
   * Show a list of your organizations on Pantheon
   *
   * @subcommand list
   *
   */
  public function all($args, $assoc_args) {
     $user = new User(new stdClass(), array());
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
   * List an organization's sites
   *
   * ## OPTIONS
   *
   * [--org=<id>]
   * : Organization id
   *
   * [--tag=<tag>]
   * : Tag name to filter sites list by
   *
   * [--add=<site>]
   * : Site to add to organization
   *
   * [--remove=<site>]
   * : Site to remove from organization
   *
   * @subcommand sites
   *
   */
  public function sites($args, $assoc_args) {
    $org_id = Input::orgid($assoc_args, 'org', null, array('allow_none' => false));
    $org = new Organization($org_id);

    if (isset($assoc_args['add'])) {
        $add = SiteFactory::instance(Input::sitename($assoc_args,'add'));
        Terminus::confirm("Are you sure you want to add %s to %s ?", $assoc_args, array($add->getName(), $org->name));
        $org->addSite($add);
        Terminus::success("Added site!");
        return true;
    }

    if (isset($assoc_args['remove'])) {
      $remove = SiteFactory::instance(Input::sitename($assoc_args,'remove'));
      Terminus::confirm("Are you sure you want to remove %s to %s ?", $assoc_args, array($remove->getName(), $org->name));
      $org->removeSite($remove);
      Terminus::success("Removed site!");
      return true;
    }

    $org->siteMemberships->fetch();
    $memberships = $org->siteMemberships->all();

    foreach ($memberships as $membership) {
      if (isset($assoc_args['tag']) && !(in_array($assoc_args['tag'], $membership->get('tags')))) {
        continue;
      }
      $site = $membership->get('site');
      $data[] = array(
        'name' => $site->name,
        'id' => $site->id,
        'service_level' => $site->service_level,
        'framework' => $site->framework,
        'created' => date('Y-m-d H:i:s', $site->created),
        'tags' => $membership->get('tags')
      );
    }
    $this->handleDisplay($data);
  }

}
Terminus::add_command( 'organizations', 'Organizations_Command' );
