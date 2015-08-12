<?php
namespace Terminus\Models;
use \Terminus\Request;
use \Terminus\Site;

class OrganizationSiteMembership {
  public $id;
  public $attributes;
  public $collection;
  public $organization;
  public $site;

  public function __construct($attributes, $options = array()) {
    $this->id = $attributes->id;
    $this->attributes = $attributes;
    $this->site = new Site($attributes->site);

    $this->collection = $options['collection'];
    $this->organization = $options['organization'];

    return $this;
  }

  public function get($attribute) {
    if(isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }
}
