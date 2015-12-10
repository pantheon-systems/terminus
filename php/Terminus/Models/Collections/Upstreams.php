<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Collections\TerminusCollection;

class Upstreams extends TerminusCollection {

  /**
   * Search available upstreams by UUID or name
   *
   * @param [string] $id_or_name UUID or name
   * @return [Upstream] $member
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
   * @param [stdClass] $model_data Data to feed into attributes of new model
   * @param [array]    $options    Data to make properties of the new model
   * @return [void]
   */
  public function add($model_data, $options = array()) {
    parent::add($model_data->attributes, $options);
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'products';
    return $url;
  }

}
