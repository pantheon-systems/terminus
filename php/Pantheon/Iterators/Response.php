<?php

namespace Pantheon\Iterators;

class Response implements \Iterator {

  private $position = -1;
  protected $responseClass = "RestRemote";
  protected $data = array();
  protected $info;
  protected $headers;
  protected $json;
  protected $_headers = array("uuid" => "Uuid", "name" => "Name");


  function __construct($raw) {
    foreach ($raw as $key => $value) {
      if ($key == "data") {
        if (is_object($value) || is_array($value)) {
          foreach ($value as $uuid => $objData) {
            $objData->uuid = $uuid;
            $class = '\Pantheon\DataWrappers\\'.$this->responseClass;
            $r = new \ReflectionClass($class);
            if ($r instanceOf \ReflectionClass) {
              $this->data[] = $r->newInstance($objData);
            } else {
              $this->_debug(get_defined_vars());
            }
          }
        }
      }
      else {
        @$this->$key = $value;
      }
    }
    $this->position = 0;
  }

  function __toString() {
    return (string) $this->getTable();
  }

  function __toJson() {
    return (!empty($this->data)) ? json_encode($this->data) : NULL;
  }

  function __get($var) {
    return $this->$var;
  }

  function __set($var, $value) {
    $this->$var = $value;
  }

  function getInfo() {
    return $this->info;
  }

  function getHeaders() {
    return $this->headers;
  }

  function getDataFromRequest() {
    if (in_array($this->info['http_code'], array(200, 201, 202, 203))) {
      return $this->data;
    }
    else {
      \Terminus::error("The call to {$this->info['url']} failed with a {$this->info['http_code']} code.");
      return FALSE;
    }
  }

  function getJsonFromRequest() {
    if (in_array($this->info['http_code'], array(200, 201, 202, 203))) {
      return $this->json;
    }
    else {
      \Terminus::error("The call to {$this->info['url']} failed with a {$this->info['http_code']} code.");
      return FALSE;
    }
  }


  function rewind() {
    $this->position = -1;
  }

  function current() {
    return ($this->valid()) ? $this->data[$this->position] : NULL;
  }

  function key() {
    return $this->position;
  }

  function next() {
    $this->position++;
    return $this->current();
  }

  function valid() {
    return isset($this->data[$this->position]);
  }

  function first() {
    return $this->data[0];
  }

  public function getTable() {
    $this->rewind();
    $table = new \cli\Table();
    $table->setHeaders($this->getTableHeaders());
    while ($obj = $this->next()) {
      $table->addRow($obj->getTableRow(array_keys($this->_headers)));
    }
    return $table->display();
  }

  public function findByName($name) {
    $this->rewind();
    while ($obj = $this->next()) {
      if ($obj->getName() == $name) {
        return $obj;
      }
    }
    return FALSE;
  }

  public function findByUUID($uuid) {
    $this->rewind();
    while ($obj = $this->next()) {
      if ($obj->getUUID() == $uuid) {
        return $obj;
      }
    }
    return FALSE;
  }

  protected function _debug($vars) {
    \Terminus::line(print_r($this, TRUE));
    \Terminus::line(print_r($vars, TRUE));
  }

  public function getTableHeaders() {
    return array_values($this->_headers);
  }

  public function respond($assoc_args) {
    if (array_key_exists("json", $assoc_args)) {
      return $this->__toJson();
    }
    else {
      return $this->__toString();
    }
  }

}