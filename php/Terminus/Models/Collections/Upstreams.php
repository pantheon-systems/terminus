<?php

namespace Terminus\Models\Collections;

class Upstreams extends NewCollection {
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Upstream';
  /**
   * @var boolean
   */
  protected $paged = false;
  /**
   * @var string
   */
  protected $url = 'products';

  /**
   * Search available upstreams by UUID or name
   *
   * @param string $id_or_name UUID or name
   * @return Upstream
   */
  public function getByIdOrName($id_or_name) {
    $members   = $this->getMemberList('id', 'longname');
    $member_id = null;
    if (isset($members[$id_or_name])) {
      $member_id = $id_or_name;
    } else {
      $member_id = array_search($id_or_name, $members);
    }
    $member = $this->get($member_id);
    return $member;
  }

  /**
   * Adds a model to this collection
   *
   * @param array $model_data  Data to feed into attributes of new model
   * @param array $arg_options Data to make properties of the new model
   * @return void
   */
  protected function add(array $model_data = [], array $arg_options = []) {
    $default_options = ['id' => $model_data['id'], 'collection' => $this,];
    $options         = array_merge($default_options, $arg_options);

    $model_name = $this->collected_class;
    $model      = new $model_name((array)$model_data['attributes'], $options);

    $this->models[$model_data['id']] = $model;
  }

}
