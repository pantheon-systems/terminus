<?php

namespace Terminus\Commands;

use Terminus\Session;
use Terminus\Commands\TerminusCommand;
use Terminus\Models\User;
use Terminus\Models\Organization;
use Terminus\Models\OrganizationSiteMembership;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Collections\UserOrganizationMemberships;

/**
 * Show information for your Pantheon organizations
 *
 * @command organizations
 */
class OrganizationsCommand extends TerminusCommand {

  /**
   * Object constructor
   *
   * @param array $options Options to construct the command object
   * @return OrganizationsCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Show a list of your organizations on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $user          = Session::getUser();
    $data          = array();
    $organizations = $user->getOrganizations();
    foreach ($organizations as $id => $org) {
      $org_data = $org->get('organization');
      $data[]   = array(
        'name' => $org_data->profile->name,
        'id' => $org->get('id'),
      );
    }

    $this->output()->outputRecordList($data);
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
   */
  public function sites($args, $assoc_args) {
    $action   = array_shift($args);
    $org_id   = $this->input()->orgId(
      array(
        'args'       => $assoc_args,
        'allow_none' => false,
      )
    );
    // TODO: clarify that these are OrganizationMemberships, not Organization models
    $orgs      = new UserOrganizationMemberships();
    $org       = $orgs->get($org_id);
    $org_info  = $org->get('organization');
    $org_model = new Organization($org_info);

    $memberships = $org->site_memberships->all();

    switch ($action) {
      case 'add':
        if (isset($assoc_args['site'])) {
          if ($this->siteIsMember($memberships, $assoc_args['site'])) {
            $this->failure(
              '{site} is already a member of {org}',
              array(
                'site' => $assoc_args['site'],
                'org' => $org_info->profile->name
              )
            );
          } else {
            $site = $this->sites->get($assoc_args['site']);
          }
        } else {
          $site = $this->sites->get(
            $this->input()->menu(
              array(
                'choices' => $this->getNonmemberSiteList($memberships),
                'message' => 'Choose site'
              )
            )
          );
        }
        $this->input()->confirm(
          array(
            'message' => 'Are you sure you want to add %s to %s ?',
            'context' => array($site->get('name'), $org_info->profile->name),
          )
        );
        $workflow = $org_model->site_memberships->addMember($site);
        $workflow->wait();
        $this->workflowOutput($workflow);
          break;
      case 'remove':
        if (isset($assoc_args['site'])) {
          if (!$this->siteIsMember($memberships, $assoc_args['site'])) {
            $this->failure(
              '{site} is not a member of {org}',
              array(
                'site' => $assoc_args['site'],
                'org' => $org_info->profile->name
              )
            );
          } else {
            $site = $this->sites->get($assoc_args['site']);
          }
        } else {
          $site = $this->sites->get(
            $this->input()->menu(
              array(
                'choices' => $this->getMemberSiteList($memberships),
                'message' => 'Choose site',
              )
            )
          );
        }
        $member = $org_model->site_memberships->get($site->get('id'));
        $this->input()->confirm(
          array(
            'message' => 'Are you sure you want to remove %s from %s ?',
            'context' => array($site->get('name'), $org_info->profile->name),
          )
        );
        $workflow = $member->removeMember();
        $workflow->wait();
        $this->workflowOutput($workflow);
          break;
      case 'list':
      default:
        $data = [];
        foreach ($memberships as $membership) {
          if (isset($assoc_args['tag'])
            && !(in_array($assoc_args['tag'], $membership->get('tags')))
          ) {
            continue;
          }
          $site       = $membership->get('site');
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
          if (isset($site->frozen) && (boolean)$site->frozen) {
            $data_array['frozen'] = true;
          }
          $data_array['created'] = date(
            TERMINUS_DATE_FORMAT,
            $data_array['created']
          );
          $data[] = $data_array;
        }
        if (empty($data)) {
          $message = 'No sites match your ';
          if (empty($assoc_args)
            || ((count($assoc_args) == 1) && (isset($assoc_args['org'])))
          ) {
            $message .= 'criterion.';
          } else {
            $message .= 'criteria.';
          }
          $this->log()->info($message);
        }
        $this->output()->outputRecordList($data);
          break;
    }
  }

  /**
   * List an organization's team members
   *
   * ## OPTIONS
   *
   * <list|add-member|remove-member|change-role>
   * : Options are list, add-member, remove-member, and change-role.
   *
   * [--org=<id|name>]
   * : Organization UUID or name
   *
   * [--member=<email>]
   * : Email of the member to add. Member will receive an invite
   *
   * [--role=<role>]
   * : Role for the new member to act as. Options are admin, team_member, and
   *   developer.
   *
   * @subcommand team
   */
  public function team($args, $assoc_args) {
    $action = array_pop($args);

    $org_id = $this->input()->orgId(
      array(
        'args'       => $assoc_args,
        'allow_none' => false,
      )
    );
    $orgs = new UserOrganizationMemberships();
    $org  = $orgs->get($org_id);
    if (is_null($org)) {
      $message  = 'The organization {org} is either invalid or you haven\'t';
      $message .= ' permission sufficient to access its data.';
      $this->failure(
        $message,
        array('org' => $assoc_args['org'])
      );
    }
    $org_info     = $org->get('organization');
    $org_model    = new Organization($org_info);
    $role_choices = ['unprivileged', 'admin'];

    switch ($action) {
      case 'add-member':
        $email = $this->input()->string(
          [
            'args'    => $assoc_args,
            'key'     => 'member',
            'message' => 'What is the email address of the user to be added?',
          ]
        );
        $can_change_management = $org_model->getFeature('change_management');
        $role                  = $this->input()->orgRole(
          [
            'args'                  => $assoc_args,
            'can_change_management' => $can_change_management,
            'return_value'          => true,
            'key'                   => 'role',
            'message'               => 'Select a role for your new member.',
          ]
        );
        $workflow = $org->user_memberships->addMember($email, $role);
        $workflow->wait();
        $this->workflowOutput($workflow);
          break;
      case 'remove-member':
        $member = $this->input()->orgMember(
          [
            'args'            => $assoc_args,
            'autoselect_solo' => false,
            'can_pick_self'   => false,
            'message'         => 'Please select a member to remove',
            'org'             => $org,
          ]
        );
        $workflow = $member->removeMember();
        $workflow->wait();
        $this->workflowOutput($workflow);
          break;
      case 'change-role':
        $member   = $this->input()->orgMember(
          [
            'args'            => $assoc_args,
            'autoselect_solo' => false,
            'message'         => 'Please select a member to update',
            'org'             => $org,
          ]
        );
        if ($org_model->getFeature('change_management')) {
          $role_choices[] = 'team_member';
          $role_choices[] = 'developer';
        }
        $can_change_management = $org_model->getFeature('change_management');
        $role     = $this->input()->orgRole(
          [
            'args'                  => $assoc_args,
            'can_change_management' => $can_change_management,
            'return_value'          => true,
            'key'                   => 'role',
            'message'               => 'Select a role for this member.',
          ]
        );
        $workflow = $member->setRole($role);
        $this->workflowOutput($workflow);
          break;
      case 'list':
      default:
        $memberships = $org->user_memberships->all();
        $data        = [];
        foreach ($memberships as $membership) {
          $member = $membership->get('user');

          $first_name = $last_name = null;
          if (isset($member->profile->firstname)) {
            $first_name = $member->profile->firstname;
          }
          if (isset($member->profile->lastname)) {
            $last_name = $member->profile->lastname;
          }

          $data[$member->id] = [
            'first' => $first_name,
            'last'  => $last_name,
            'email' => $member->email,
            'role'  => $membership->get('role'),
            'uuid'  => $member->id,
          ];
        }
        $this->output()->outputRecordList($data);
          return $data;
    }
  }

  /**
   * Retrieves a succinct list of member sites
   *
   * @param OrganizationSiteMembership[] $memberships Members of this org
   * @return array
   */
  private function getMemberSiteList(array $memberships) {
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
   * @param OrganizationSiteMembership[] $memberships Members of this org
   * @return array
   */
  private function getNonmemberSiteList($memberships) {
    $members = $this->getMemberSiteList($memberships);
    $sites   = $this->sites->getMemberList();
    $list    = array_diff($sites, $members);
    return $list;
  }

  /**
   * Determines whether the site is a member of an org
   *
   * @param OrganizationSiteMembership[] $memberships Members of this org
   * @return bool
   */
  private function siteIsMember($memberships, $site_id) {
    $list      = $this->getMemberSiteList($memberships);
    $is_member = (
      isset($list[$site_id])
      || (array_search($site_id, $list) !== false)
    );
    return $is_member;
  }

}

