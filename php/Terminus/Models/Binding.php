<?php
namespace Terminus\Models;
use \Terminus\Request;

class Binding {
  public $id;
  public $attributes;
  public $collection;
  public $environment;

  public function __construct($attributes, $options = array()) {
    $this->id = $attributes->id;
    $this->attributes = $attributes;
    $this->collection = $options['collection'];
    $this->environment = $options['environment'];

    return $this;
  }
}
