<?php

namespace Terminus\Models;

use Terminus\Configurator;
use Terminus\Exceptions\TerminusException;
use Terminus\Request;

abstract class TerminusModel {
  protected $id;
  /**
   * @var Request
   */
  protected $request;
  /**
   * @var object
   */
  protected $attributes;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = array()) {
    if (!defined('Terminus')) {
      $configurator = new Configurator();
    }
    if ($attributes == null) {
      $attributes = new \stdClass();
    }
    if (isset($attributes->id)) {
      $this->id = $attributes->id;
    }
    foreach ($options as $var_name => $value) {
      $this->$var_name = $value;
    }
    $this->attributes = $attributes;
    $this->request    = new Request();
  }

  /**
   * Handles requests for inaccessible properties
   *
   * @param string $property Name of property being requested
   * @return mixed $this->$property
   * @throws TerminusException
   */
  public function __get($property) {
    if (property_exists($this, $property)) {
      return $this->$property;
    }

    $trace = debug_backtrace();
    throw new TerminusException(
      'Undefined property $var->${property} in {file} on line {line}',
      [
        'property' => $property,
        'file' => $trace[0]['file'],
        'line' => $trace[0]['line']
      ],
      1
    );
    return null;
  }

  /**
   * Fetches this object from Pantheon
   *
   * @param array $options Params to pass to url request
   * @return TerminusModel $this
   */
  public function fetch(array $options = array()) {
    $fetch_args = array();
    if (isset($options['fetch_args'])) {
      $fetch_args = $options['fetch_args'];
    }

    $options = array_merge(
      array('options' => array('method' => 'get')),
      $this->getFetchArgs(),
      $fetch_args
    );

    $results = $this->request->request(
      $this->getFetchUrl(),
      $options
    );

    $data = $results['data'];
    $data = $this->parseAttributes($data);
    $this->attributes = $data;

    return $this;
  }

  /**
   * Modify response data between fetch and assignment
   *
   * @param [object] $data attributes received from API response
   * @return [object] $data
   */
  public function parseAttributes($data) {
    return $data;
  }

  /**
   * Retrieves attribute of given name
   *
   * @param string $attribute Name of the key of the desired attribute
   * @return mixed Value of the attribute, or null if not set.
   */
  public function get($attribute) {
    if ($this->has($attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }

  /**
   * Checks whether the model has an attribute
   *
   * @param string $attribute Name of the attribute key
   * @return boolean True if attribute exists, false otherwise
   */
  public function has($attribute) {
    $isset = isset($this->attributes->$attribute);
    return $isset;
  }

  /**
   * Give necessary args for collection data fetching
   *
   * @return array
   */
  protected function getFetchArgs() {
    return array();
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    return '';
  }

}
