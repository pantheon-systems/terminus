<?php
/**
 * Created by PhpStorm.
 * User: stovak
 * Date: 3/25/14
 * Time: 11:36 AM
 */

namespace Pantheon\Iterators;


class EnvironmentList extends Response {

  protected $responseClass = "Environment";

  protected $_headers = array("name" => "Environment", "created" => "Created", "locked" => "Locked");

  function __construct($raw) {
    $class = "\Pantheon\DataWrappers\\$this->responseClass";
    foreach ($raw as $key => $value) {
      if ($key == "data") {
        if (is_object($value) || is_array($value)) {
          foreach ($value as $name => $objData) {
            $objData->name = $name;
            $this->data[] = new $class($objData);
          }
        }
      }
      else {
        $this->$key = $value;
      }
    }
    $this->position = 0;
  }

}