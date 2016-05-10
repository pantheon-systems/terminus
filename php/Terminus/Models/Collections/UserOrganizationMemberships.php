<?php

namespace Terminus\Models\Collections;

class UserOrganizationMemberships extends NewCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\UserOrganizationMembership';
  /**
   * @var boolean
   */
  protected $paged = true;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return UserOrganizationMemberships
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "users/{$this->user->id}/memberships/organizations";
  }

  /**
   * Retrieves the model of the given ID
   *
   * @param string $id ID or name of desired organization
   * @return UserOrganizationMembership $model
   */
  public function get($id) {
    $model = null;
    if (isset($this->models[$id])) {
      $model = $this->models[$id];
    } else {
      foreach ($this->models as $model_candidate) {
        if ((isset($model_candidate->profile)
            && ($id == $model_candidate->profile->name))
          || (isset($model_candidate->get('organization')->profile)
            && $model_candidate->get('organization')->profile->name == $id)
        ) {
          $model = $model_candidate;
          break;
        }
      }
    }
    return $model;
  }

  /**
   * Adds a model to this collection
   *
   * @param array $model_data  Data to feed into attributes of new model
   * @param array $arg_options Data to make properties of the new model
   * @return void
   */
  protected function add(array $model_data = [], array $arg_options = []) {
    $default_options = [
      'id'   => $model_data['id'],
      'user' => $this->user,
    ];
    $options         = array_merge($default_options, $arg_options);
    parent::add($model_data, $options);
  }
  
}
