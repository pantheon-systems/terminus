<?php

namespace Terminus\Collections;

class OrganizationSiteMemberships extends TerminusCollection {
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return OrganizationSiteMemberships
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->organization = $options['organization'];
    $this->url = "organizations/{$this->organization->id}/memberships/sites";
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data  Data to feed into attributes of new model
   * @param array  $arg_options Data to make properties of the new model
   * @return void
   */
  public function add($model_data, array $arg_options = []) {
    $default_options = [
      'id'           => $model_data->id,
      'collection'   => $this,
    ];
    $options         = array_merge($default_options, $arg_options);
    parent::add($model_data, $options);
  }

  /**
   * Adds a site to this organization
   *
   * @param Site $site Site object of site to add to this organization
   * @return Workflow
   */
  public function create($site) {
    $workflow = $this->organization->workflows->create(
      'add_organization_site_membership',
      ['params' => ['site_id' => $site->id, 'role' => 'team_member',],]
    );
    return $workflow;
  }

  /**
   * Retrieves the model with site of the given UUID or name
   *
   * @param string $id UUID or name of desired site membership instance
   * @return OrganizationSiteMembership
   */
  public function get($id) {
    $models = $this->models;
    $model  = null;
    if (isset($models[$id])) {
      $model = $models[$id];
    } else {
      foreach ($models as $key => $membership) {
        if ($membership->site->get('name') == $id) {
          $model = $membership;
          continue;
        }
      }
    }
    return $model;
  }

  /**
   * Retrieves the matching site from model members
   *
   * @param string $site_id ID or name of desired site
   * @return Site $site
   * @throws TerminusException
   */
  public function getSite($site_id) {
    $memberships = $this->all();
    foreach ($memberships as $membership) {
      $site = $membership->site;
      if (in_array($site_id, [$site->id, $site->get('name'),])) {
        return $site;
      }
    }
    throw new TerminusException(
      'This user does is not a member of an organizaiton identified by {id}.',
      ['id' => $site_id,]
    );
  }

  /**
   * Determines whether a site is a member of this collection
   *
   * @param Site $site Site to determine membership of
   * @return bool
   */
  public function siteIsMember($site) {
    try {
      $this->getSite($site);
      return true;
    } catch (TerminusException $e) {
      return false;
    }
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'organization';
    return $owner_name;
  }

}
