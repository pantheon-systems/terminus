<?php

namespace Terminus\Commands;

use Terminus\Models\Organization;
use Terminus\Models\OrganizationSiteMembership;
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
    $this->user  = $this->sites->user;
  }

  /**
   * Show a list of your organizations on Pantheon
   *
   * @subcommand list
   */
  public function all($args, $assoc_args) {
    $data = [];
    $org_memberships = $this->user->org_memberships->fetch()->all();
    foreach ($org_memberships as $org_membership) {
      $org = $org_membership->organization;
      $data[]   = [
        'name' => $org->get('profile')->name,
        'id'   => $org->get('id'),
      ];
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
    $action = array_shift($args);
    $org_id = $this->input()->orgId(
      ['args' => $assoc_args, 'allow_none' => false,]
    );
    $org    = $this->user->org_memberships->fetch()->get($org_id)->organization;

    switch ($action) {
      case 'add':
        $this->addMemberSite($org, $assoc_args);
          break;
      case 'remove':
        $this->removeMemberSite($org, $assoc_args);
          break;
      case 'list':
      default:
        $tag = $this->input()->optional(
          [
            'choices' => $assoc_args,
            'key'     => 'tag',
          ]
        );
        $this->listMemberSites($org, $tag);
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
    $action = array_shift($args);
    $org_id = $this->input()->orgId(
      ['args' => $assoc_args, 'allow_none' => false,]
    );
    $org    = $this->user->org_memberships->fetch()->get($org_id)->organization;
    
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
        $can_change_management = $org->getFeature('change_management');
        $role                  = $this->input()->orgRole(
          [
            'args'                  => $assoc_args,
            'can_change_management' => $can_change_management,
            'return_value'          => true,
            'key'                   => 'role',
            'message'               => 'Select a role for your new member.',
          ] 
        );
        $workflow = $org->user_memberships->create($email, $role);
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
   * Adds a site to an organization
   * 
   * @param Organization $org An object representing one organization
   * @param array        $assoc_args Parameters from the command line
   * @throws TerminusException
   * @return void
   */
  private function addMemberSite($org, $assoc_args) {
    $org->site_memberships->fetch();
    if (isset($assoc_args['site'])) {
      $site = $this->sites->get($assoc_args['site']);
      if ($org->site_memberships->siteIsMember($site)) {
        $this->failure(
          '{site} is already a member of {org}',
          [
            'site' => $assoc_args['site'],
            'org'  => $org->get('profile')->name,
          ]
        );
      }
    } else {
      $site = $this->sites->get(
        $this->input()->menu(
          [
            'choices' => $this->getNonmemberSiteList($org),
            'message' => 'Choose site',
          ]
        )
      );
    }
    $this->input()->confirm(
      [
        'message' => 'Are you sure you want to add %s to %s ?',
        'context' => [$site->get('name'), $org->get('profile')->name,],
      ]
    );
    $workflow = $org->site_memberships->create($site);
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

  /**
   * Displays a list of member sites of the given organization
   * 
   * @param Organization $org An object representing one organization
   * @param string       $tag A tag to filter member sites by
   * @return void
   */
  private function listMemberSites($org, $tag = null) {
    $memberships = array_filter(
      $org->site_memberships->fetch()->all(),
      function ($membership) use ($tag) {
        $matches_tag = (
          is_null($tag)
          || in_array($tag, $membership->get('tags'))
        );
        return $matches_tag;
      }
    );

    $data = array_map(
      function ($membership) {
        $site = $membership->site;
        $info = [
          'name'          => $site->get('name'),
          'id'            => $site->id,
          'service_level' => $site->get('service_level'),
          'framework'     => $site->get('framework'),
          'created'       => date(
            TERMINUS_DATE_FORMAT, $site->get('created')
          ),
          'tags'          => $membership->get('tags'),
        ];
        return $info;
      },
      $memberships
    );

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
  }

  /**
   * Retrieves a succinct list of non-member sites
   *
   * @param Organization $org An object representing one organization
   * @return array
   */
  private function getNonmemberSiteList($org) {
    $members = $org->site_memberships->list();
    $sites   = $this->sites->fetch()->list();
    $list    = array_diff($sites, $members);
    return $list;
  }

  /**
   * Removes a site from an organization
   *
   * @param Organization $org An object representing one organization
   * @param array        $assoc_args Parameters from the command line
   * @throws TerminusException
   * @return void
   */
  private function removeMemberSite($org, $assoc_args) {
    $org->site_memberships->fetch();
    if (isset($assoc_args['site'])) {
      $site = $this->sites->get($assoc_args['site']);
      if (!$org->site_memberships->siteIsMember($site)) {
        $this->failure(
          '{site} is not a member of {org}',
          [
            'site' => $assoc_args['site'],
            'org'  => $org->get('profile')->name,
          ]
        );
      }
    } else {
      $site = $this->sites->get(
        $this->input()->menu(
          [
            'choices' => $org->site_memberships->list(),
            'message' => 'Choose site',
          ]
        )
      );
    }

    $member = $org->site_memberships->get($site->id);
    $this->input()->confirm(
      [
        'message' => 'Are you sure you want to remove %s from %s ?',
        'context' => [$site->get('name'), $org->get('profile')->name,],
      ]
    );
    $workflow = $member->delete();
    $workflow->wait();
    $this->workflowOutput($workflow);
  }

}

