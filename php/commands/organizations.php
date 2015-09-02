<?php

use \Terminus\Models\User;
use \Terminus\Utils;
use \Terminus\Auth;
use \Terminus\SiteFactory;
use \Terminus\Models\Organization;
use \Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\UserOrganizationMemberships;
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
     foreach ($user->getOrganizations() as $id => $org) {
       $data[] = array(
         'name' => $org->get('name'),
         'id' => $org->get('id'),
       );
     }

     $this->handleDisplay($data);
  }

  /**
   * List an organization's sites
   *
   * ## OPTIONS
   *
   * [--org=<id|name>]
   * : Organization UUID or name
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
    $orgs   = new UserOrganizationMemberships();
    $org    = $orgs->get($org_id);
    $memberships = $org->site_memberships;

    if (isset($assoc_args['add'])) {
      $site = SiteFactory::instance(Input::sitename($assoc_args, 'add'));
      Terminus::confirm(
        'Are you sure you want to add %s to %s ?',
        $assoc_args,
        array($site->get('name'), $org->get('name'))
      );
      $memberships->addMember($site);
      Terminus::success('Added site!');
      return true;
    }

    if (isset($assoc_args['remove'])) {
      $site_id = $assoc_args['remove'];
      $member = $memberships->get($assoc_args['remove']);
      $site = $member->get('site');
      Terminus::confirm(
        'Are you sure you want to remove %s from %s ?',
        $assoc_args,
        array($site->name, $org->get('name'))
      );
      $member->removeMember();
      Terminus::success('Removed site!');
      return true;
    }

    $memberships = $org->getSites();
    foreach ($memberships as $membership) {
      if (isset($assoc_args['tag']) && !(in_array($assoc_args['tag'], $membership->get('tags')))) {
        continue;
      }
      $site = $membership->get('site');
      $data_array = array(
        'name'          => null,
        'id'            => null,
        'service_level' => null,
        'framework'     => null,
        'created'       => null,
        'tags'          => $membership->get('tags')
      );
      foreach ($data_array as $key => $value) {
        if (($value == null) && isset($site->$key)) {
          $data_array[$key] = $site->$key;
        }
      }
      $data_array['created'] = date('Y-m-dTH:i:s', $data_array['created']);
      $data[] = $data_array;
    }
    $this->handleDisplay($data);
  }

}
Terminus::add_command( 'organizations', 'Organizations_Command' );
