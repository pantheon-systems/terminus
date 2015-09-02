<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;

class Bindings extends TerminusCollection {

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @return [void]
   */
  public function fetch($paged = false) {
    $results = $this->getCollectionData();

    foreach (get_object_vars($results['data']) as $id => $model_data) {
      $model_data->id = $id;
      $this->add($model_data, $options = array('collection' => $this));
    }
  }

  /**
   * Get bindings by type
   *
   * @param [string] $type e.g. "appserver", "db server", etc
   * @return [array] $bindings
   */
  public function getByType($type) {
    $models = array_filter(
      $this->all(),
      function($binding) use ($type) {
        $is_valid = (
          $binding->get('type') == $type
          && !$binding->get('failover')
          && !$binding->get('slave_of')
        );
        return $is_valid;
      }
    );

    $bindings = array_values($models);
    return $bindings;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf('sites/%s/bindings', $this->environment->site->getId());
    return $url;
  }

  /**
   * Names the model-owner of this collection, false if DNE
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'environments';
    return $owner_name;
  }

}
