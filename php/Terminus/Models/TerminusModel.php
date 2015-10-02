<?php

namespace Terminus\Models;

use TerminusCommand;

abstract class TerminusModel {
  protected $id;
  private $attributes;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes Attributes of this model
   * @param [array]    $options    Options to set as $this->key
   * @return [TerminusModel] $this
   */
  public function __construct($attributes = null, $options = array()) {
    if ($attributes == null) {
      $attributes = new \stdClass();
    }
    foreach ($options as $var_name => $value) {
      $this->$var_name = $value;
    }
    $this->attributes = $attributes;
  }

  /**
   * Handles requests for inaccessable properties
   *
   * @param [string] $property Name of property being requested
   * @return [mixed] $this->$property
   */
  public function __get($property) {
    if (property_exists($this, $property)) {
      return $this->$property;
    }

    $trace = debug_backtrace();
    trigger_error(
      sprintf(
        'Undefined property $var->$%s in %s on line %s',
        $property,
        $trace[0]['file'],
        $trace[0]['line']
      ),
      E_USER_NOTICE
    );
    return null;
  }

  /**
   * Fetches this object from Pantheon
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [TerminusModel] $this
   */
  public function fetch($paged = false) {
    $function_name = 'simple_request';
    if ($paged) {
      $function_name = 'paged_request';
    }

    $results = TerminusCommand::$function_name(
      $this->getFetchUrl(),
      $this->getFetchArgs()
    );

    $this->attributes = $results['data'];
    return $this;
  }

  /**
   * Retrieves attribute of given name
   *
   * @param [string] $attribute Name of the key of the desired attribute
   * @return [mixed] $this->attributes->$attribute
   */
  public function get($attribute) {
    if (isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }

  /**
   * Give necessary args for collection data fetching
   *
   * @return [array]
   */
  protected function getFetchArgs() {
    return array();
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    return '';
  }

}
