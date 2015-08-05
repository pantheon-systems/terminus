<?php

namespace Terminus;
use \ReflectionClass;
use \Terminus\Request;
use \Terminus\Collections\Bindings;

class Instrument {
  private $_id;
  private $_attributes;
  private $_bindings;

  private $_user;

  public function __construct(User $user, $data = null) {
    $this->_user = $user;
    if(property_exists($data, 'id')) {
      $this->_id = $data->id;
    }
    $this->_attributes = $data;

    if(is_object($data)) {
      // if we receive an instrument object from the api hydrate the vars
      $instrument_properties = get_object_vars($data);
      // iterate our local properties setting them where available in the imported object
      foreach(get_object_vars($this) as $key => $value) {
        if(array_key_exists($key,$instrument_properties)) {
          $this->$key = $instrument_properties[$key];
        }
      }
    }
  }

  public function get($attribute = 'id') {
    return $this->_attributes->$attribute;
  }

  /**
   * Returns ID of instrument object
   *
   * @return [string] UUID of instrument
   */
  public function getId() {
    return $this->_id;
  }
}
