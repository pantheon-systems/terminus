<?php

namespace Terminus\Models\Collections;

class Upstreams extends NewCollection {
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Upstream';
  /**
   * @var string
   */
  protected $url = 'products';

  /**
   * Search available upstreams by UUID or name
   *
   * @param string $id UUID or name
   * @return Upstream
   */
  public function get($id) {
    $member = parent::get($id);
    if (!is_null($member)) {
      return $member;
    }
    $members = $this->list('name', 'id');
    if (isset($members[$id])) {
      return $this->models[$members[$id]];
    }
    return $member;
  }

}
