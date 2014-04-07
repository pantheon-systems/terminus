<?php

namespace Pantheon\DataWrappers;


class RestRemote {

  protected $uuid;
  protected $name;

  /**
   * Take incoming variables, flattent them out and assign them to protected vars
   *
   * @param string $raw
   * @author stovak
   */

  function __construct($raw) {
    foreach ($raw as $key => $value) {
      $this->__set(str_replace($this->getBaseClass() . "_", "", $key), $value);
    }
  }


  function __toString() {
    return print_r($this, TRUE);
  }

  function __get($var) {
    if (in_array($var, array("name", "uuid"))) {
      $call = "get" . ucfirst($var);
      return $this->$call();
    }
    else {
      return $this->$var;
    }
  }

  function __set($var, $value) {
    $this->$var = $value;
  }

  public function getUUID() {
    return $this->uuid;
  }

  public function getName() {
    return $this->name;
  }

  public function getBaseClass() {
    return strtolower(str_replace(__NAMESPACE__, "", __CLASS__));
  }

  public function getTableRow(array $columns) {
    $toReturn = array();
    foreach ($columns as $varName) {
      $toReturn[] = $this->$varName;
    }
    return $toReturn;
  }

  protected function _debug(array $vars) {
    \Terminus::line(print_r($this, TRUE));
    \Terminus::line(print_r($vars, TRUE));
  }

}