<?php

namespace Terminus\Models;

use Terminus\Configurator;
use Terminus\Request;

abstract class NewModel {
  /**
   * @var string
   */
  public $id;
  /**
   * @var array
   */
  protected $attributes = [];
  /**
   * @var Request
   */
  protected $request;
  /**
   * @var string URL to access this model's attributes from the API
   */
  protected $url;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = []) {
    if (!defined('Terminus')) {
      $configurator = new Configurator();
    }
    $this->attributes = $attributes;
    if (is_null($attributes)) {
      $this->attributes = (object)[];
    }
    foreach ($options as $var_name => $value) {
      $this->$var_name = $value;
    }
    $this->request = new Request();
  }

  /**
   * Fetches this object's attributes from Pantheon
   *
   * @param array $arg_options Params to pass to url request
   * @return TerminusModel $this
   */
  public function fetch(array $arg_options = []) {
    $default_options = [
      'method' => 'get',
      'params' => [],
    ];
    $options  = array_merge_recursive($default_options, $arg_options);
    $response = $this->request->request($this->url, $options);
    $this->attributes = (object)array_merge(
      (array)$this->attributes,
      $this->parseAttributes((array)$response['data'])
    );
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
   * Retrieves attribute of given name
   *
   * @param string $attribute Name of the key of the desired attribute
   * @param mixed  $value     Value of the desired attribute
   * @return void
   */
  public function set($attribute, $value) {
    $this->attributes->$attribute = $value;
  }

}
