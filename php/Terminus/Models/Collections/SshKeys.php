<?php

namespace Terminus\Models\Collections;

class SshKeys extends TerminusCollection {

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return SshKeys $this
   */
  public function fetch(array $options = array()) {
    $results = $this->getCollectionData($options);
    $data    = $this->objectify($results['data']);

    foreach (get_object_vars($data) as $uuid => $ssh_key) {
      $model_data = (object)['id' => $uuid, 'key' => $ssh_key,];
      $this->add($model_data);
    }

    return $this;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'users/' . $this->user->id . '/keys';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'user';
    return $owner_name;
  }

}
