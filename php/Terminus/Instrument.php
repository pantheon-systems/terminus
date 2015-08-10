<?php

namespace Terminus;
use \ReflectionClass;
use \Terminus\Request;
use \Terminus\Collections\Bindings;

class Instrument {
  private $id;
  private $attributes;

  private $user;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes
   * @param [array]    $options
   * @return [Instrument] $this
   */
  public function __construct($attributes, $options = array()) {
    foreach($options as $var_name => $value) {
      $this->$var_name = $value;
    }
    $this->attributes = $attributes;
  }

  public function get($attribute) {
    if(isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }
}
