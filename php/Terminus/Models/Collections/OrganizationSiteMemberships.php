<?php

namespace Terminus\Models\Collections;

class OrganizationSiteMemberships extends NewCollection {
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\OrganizationSiteMembership';
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
    $this->url          =
      "organizations/{$this->organization->id}/memberships/sites";
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
   * Determines whether a site is a member of this collection
   * 
   * @param Site $site Site to determine membership of
   * @return bool
   */
  public function siteIsMember($site) {
    foreach ($this->models as $model) {
      if ($site->id == $model->site->id) {
        return true;
      }
    }
    return false;
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data  Data to feed into attributes of new model
   * @param array  $arg_options Data to make properties of the new model
   * @return void
   */
  protected function add($model_data, array $arg_options = []) {
    $default_options = [
      'id'           => $model_data->id,
      'memberships'  => [$this,],
    ];
    $options         = array_merge($default_options, $arg_options);
    parent::add($model_data, $options);
  }

}
