<?php

namespace Terminus\Models;

use Terminus\Request;

abstract class TerminusModel {
  /**
   * @var string
   */
  public $id;
  /**
   * @var array Arguments for fetching this model's information
   */
  protected $args = [];
  /**
   * @var object
   */
  public $attributes;
  /**
   * @var Request
   */
  protected $request;
  /**
   * @var string The URL at which to fetch this model's information
   */
  protected $url;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = []) {
    if (is_object($attributes)) {
      $this->attributes = $attributes;
    } else {
      $this->attributes = (object)[];
    }
    $this->id = $this->attributes->id;
    $this->request = new Request();
  }

  /**
   * Fetches this object from Pantheon
   *
   * @param array $args Params to pass to request
   * @return TerminusModel $this
   */
  public function fetch(array $args = []) {
    $options = array_merge(
      ['options' => ['method' => 'get',],],
      $this->args,
      $args
    );
    $results = $this->request->request($this->url, $options);
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

}
