<?php

use \Terminus\Models\User;
use \Terminus\Utils;
use \Terminus\Auth;
use \Terminus\Models\Organization;
use \Terminus\Models\Collections\OrganizationSiteMemberships;
use \Terminus\Models\Collections\Sites;
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
    $this->sites = new Sites();
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

    $this->outputter->outputRecordList($data);
  }

  /**
   * List an organization's sites
   *
   * ## OPTIONS
   *
   * <add|remove|list>
   * : subfunction to run
   *
   * [--org=<id|name>]
   * : Organization UUID or name
   *
   * [--tag=<tag>]
   * : Tag name to filter sites list by
   *
   * [--site=<site>]
   * : Site to add to or remove from organization
   *
   * @subcommand sites
   *
   */
  public function sites($args, $assoc_args) {
    $action   = array_shift($args);
    $org_id   = Input::orgid($assoc_args, 'org', null, array('allow_none' => false));
    $orgs     = new UserOrganizationMemberships();
    $org      = $orgs->get($org_id);
    $org_info = $org->get('organization');

    $memberships = $org->site_memberships->all();

    switch ($action) {
      case 'add':
        if (isset($assoc_args['site'])) {
          if ($this->siteIsMember($memberships, $assoc_args['site'])) {
            $this->logger->error(
              sprintf(
                '%s is already a member of %s',
                $assoc_args['site'],
                $org_info->profile->name
              )
            );
            exit;
          } else {
            $site = $this->sites->get($assoc_args['site']);
          }
        } else {
          $site = $this->sites->get(
            Input::menu(
              $this->getNonmemberSiteList($memberships),
              null,
              'Choose site'
            )
          );
        }
        Terminus::confirm(
          'Are you sure you want to add %s to %s ?',
          $assoc_args,
          array($site->get('name'), $org_info->profile->name)
        );
        $workflow = $org->site_memberships->addMember($site);
        $workflow->wait();
        $this->workflowOutput($workflow);
        break;
      case 'remove':
        if (isset($assoc_args['site'])) {
          if (!$this->siteIsMember($memberships, $assoc_args['site'])) {
            $this->logger->error(
              sprintf(
                '%s is not a member of %s',
                $assoc_args['site'],
                $org_info->profile->name
              )
            );
            exit;
          } else {
            $site = $this->sites->get($assoc_args['site']);
          }
        } else {
          $site = $this->sites->get(
            Input::menu(
              $this->getMemberSiteList($memberships),
              null,
              'Choose site'
            )
          );
        }
        $member = $org->site_memberships->get($site->get('id'));
        Terminus::confirm(
          'Are you sure you want to remove %s from %s ?',
          $assoc_args,
          array($site->get('name'), $org_info->profile->name)
        );
        $workflow = $member->removeMember();
        $workflow->wait();
        $this->workflowOutput($workflow);
        break;
      case 'list':
      default:
        foreach ($memberships as $membership) {
          if (
            isset($assoc_args['tag'])
            && !(in_array($assoc_args['tag'], $membership->get('tags')))
          ) {
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
        $this->outputter->outputRecordList($data);
        break;
    }
  }

  /**
   * Retrieves a succinct list of member sites
   *
   * @param [array] $memberships Members of this org
   * @return [array] $list
   */
  private function getMemberSiteList($memberships) {
    $list = array();
    foreach ($memberships as $membership) {
      $site = $membership->get('site');
      $list[$site->id] = $site->name;
    }
    return $list;
  }

  /**
   * Retrieves a succinct list of non-member sites
   *
   * @param [array] $memberships Members of this org
   * @return [array] $list
   */
  private function getNonmemberSiteList($memberships) {
    $members = $this->getMemberSiteList($memberships);
    $sites = $this->sites->getMemberList();
    $list = array_diff($sites, $members);
    return $list;
  }

  /**
   * Determines whether the site is a member of an org
   *
   * @param [array] $memberships Members of this org
   * @return [boolean] $is_member
   */
  private function siteIsMember($memberships, $site_id) {
    $list      = $this->getMemberSiteList($memberships);
    $is_member = (
      isset($list[$site])
      || (array_search($site_id, $list) !== false)
    );
    return $is_member;
  }

}

Terminus::add_command('organizations', 'Organizations_Command');
