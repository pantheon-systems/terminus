<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Binding;

class Bindings extends TerminusCollection {

  /**
   * Get bindings by type
   *
   * @param string $type e.g. "appserver", "db server", etc
   * @return Binding[]
   */
  public function getByType($type) {
    $models = array_filter(
      $this->all(),
      function(Binding $binding) use ($type) {
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
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf('sites/%s/bindings', $this->environment->site->get('id'));
    return $url;
  }

  /**
   * Names the model-owner of this collection.
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'environment';
    return $owner_name;
  }

}
