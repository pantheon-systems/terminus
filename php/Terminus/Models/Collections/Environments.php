<?php

namespace Terminus\Models\Collections;

use Terminus\Request;
use Terminus\Model\Environment;
use \TerminusCommand;

class Environments extends TerminusCollection {

  /**
   * List Environment IDs, with Dev/Test/Live first
   *
   * @return [array] $ids
   */
  public function ids() {
    $ids = array_keys($this->getMembers());

    # Reorder environments to put dev/test/live first
    $default_ids  = array('dev', 'test', 'live');
    $multidev_ids = array_diff($ids, $default_ids);
    $ids          = array_merge($default_ids, $multidev_ids);

    return $ids;
  }

  /**
   * Returns a list of all multidev environments on the collection-owning Site
   *
   * @return [array] $environment An array of all Environment objects
   */
  public function multidev() {
    $environments = array_filter($this->getMembers(), function($environment) {
      return $environment->isMultidev();
    });
    return $environments;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->getId() . '/environments';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'site';
    return $owner_name;
  }

}
