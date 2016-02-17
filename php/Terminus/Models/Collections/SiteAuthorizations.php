<?php

namespace Terminus\Models\Collections;

class SiteAuthorizations extends TerminusCollection {

  /**
   * Adds a model to this collection
   *
   * @param object $model_data Data to feed into attributes of new model
   * @param array  $options    Data to make properties of the new model
   * @return SiteAuthorization
   */
  public function add($model_data, array $options = []) {
    $model   = $this->getMemberName();
    $owner   = $this->getOwnerName();
    $options = array_merge(
      [
        'id'         => $model_data->id,
        'collection' => $this,
      ],
      $options
    );

    $options[$owner] = $this->$owner;

    $model    = new $model($model_data, $options);
    $model_id = $model_data->id;
    if (property_exists($model_data, 'environment')) {
      $model_id .= '_' . $model_data->environment;
    }

    $this->models[$model_id] = $model;
    return $model;
  }

  /**
   * Retrieves the model of the given ID
   *
   * @param string $id ID of desired model instance
   * @return TerminusModel $this->models[$id]
   * @throws TerminusException
   */
  public function get($id, $env = null) {
    $models = $this->getMembers();
    if (isset($models[$id])) {
      return $models[$id];
    }
    $model = explode('\\', $this->getMemberName());
    throw new TerminusException(
      'Could not find {model} "{id}"',
      array(
        'model' => strtolower(array_pop($model)),
        'id'    => $id,
      ),
      1
    );
  }


  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->get('id') . '/authorizations';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'site';
    return $owner_name;
  }

}
