<?php

namespace Terminus\Models;

class Upstream extends NewModel {

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct ($attributes, array $options = []) {
    parent::__construct($attributes->attributes, $options);
  }

}
