<?php

namespace Terminus\Models\Collections;

use Terminus\Request;

abstract class NewCollection {
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\NewModel';
  /**
   * @var TerminusModel[]
   */
  protected $models = [];
  /**
   * @var boolean
   */
  protected $paged = false;
  /**
   * @var Request
   */
  protected $request;
  /**
   * @var string URL to access this collection's data from the API
   */
  protected $url;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return TerminusCollection
   */
  public function __construct(array $options = []) {
    $this->request = new Request();
  }

  /**
   * Retrieves all models
   *
   * @return TerminusModel[]
   */
  public function all() {
    $models = array_values($this->models);
    return $models;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $arg_options params to pass to url request
   * @return TerminusCollection $this
   */
  public function fetch(array $arg_options = []) {
    $default_options = [
      'method' => 'get',
      'paged'  => $this->paged,
      'params' => [],
    ];
    $options = array_merge_recursive($default_options, $arg_options);

    if ($options['paged']) {
      $response = $this->request->pagedRequest($this->url, $options);
    } else {
      $response = $this->request->request($this->url, $options);
    }

    foreach ((array)$response['data'] as $id => $model_data) {
      if (!isset($model_data->id)) {
        $model_data->id = $id;
      }
      $this->add($model_data);
    }

    return $this;
  }

  /**
   * Retrieves the model of the given ID
   *
   * @param string $id ID of desired model instance
   * @return TerminusModel $this->models[$id]
   */
  public function get($id) {
    if ($this->has($id)) {
      return $this->models[$id];
    }
    return null;
  }

  /**
   * Filters the collection's models by the given array
   *
   * @param array $filters Attributes to match during filtration
   *   e.g. ['category' => 'other', 'type' => 'normal',]
   * @return NewCollection
   */
  public function filter(array $filters = []) {
    foreach ($filters as $attribute => $value) {
      $this->models = array_filter(
        $this->models,
        function ($model) use ($attribute, $value) {
          $is_match = $model->get($attribute) == $value;
          return $is_match;
        }
      );
    }
    return $this;
  }

  /**
   * Checks whether the collection has a specific model
   *
   * @param string $id ID of the model to check for
   * @return boolean True if model exists, false otherwise
   */
  public function has($id) {
    $isset = isset($this->models[$id]);
    return $isset;
  }

  /**
   * List Model IDs
   *
   * @return string[] Array of all model IDs
   */
  public function ids() {
    $ids = array_keys($this->models);
    return $ids;
  }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value
   *
   * @param string $key   Name of attribute to make array keys
   * @param mixed  $value Name(s) of attribute(s) to comprise array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
  public function list($key = 'id', $value = 'name') {
    $members = array_combine(
      array_map(
        function($member) use ($key) {
          return $member->get($key);
        },
        $this->models
      ),
      array_map(
        function($member) use ($value) {
          if (is_scalar($value)) {
            return $member->get($value);
          }
          $list = [];
          foreach ($value as $item) {
            $list[$item] = $member->get($item);
          }
          return $list;
        },
        $this->models
      )
    );
    return $members;
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data  Data to feed into attributes of new model
   * @param array  $arg_options Data to make properties of the new model
   * @return void
   */
  protected function add($model_data, array $arg_options = []) {
    $default_options = ['id' => $model_data->id, 'collection' => $this,];
    $options         = array_merge($default_options, $arg_options);

    $model_name = $this->collected_class;
    $model      = new $model_name($model_data, $options);

    $this->models[$model_data->id] = $model;
  }

}
