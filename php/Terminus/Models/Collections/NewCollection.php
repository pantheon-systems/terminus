<?php

namespace Terminus\Models\Collections;

use Terminus\Models\TerminusModel;
use Terminus\Request;

abstract class NewCollection {
  /**
   * @var string
   */
  protected $collected_class = 'NewModel';
  /**
   * @var TerminusModel[]
   */
  protected $models = [];
  /**
   * @var boolean
   */
  protected $paged = true;
  /**
   * @var Request
   */
  protected $request;
  /**
   * @var string URL to access this collection's data from the API
   */
  protected $url;

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options To be set to $this->key
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
   * @param array $options params to pass to url request
   * @return TerminusCollection $this
   */
  public function fetch(array $options = []) {
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
      if (!isset($model_data['id'])) {
        $model_data['id'] = $id;
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
    if (isset($this->has($id))) {
      return $this->models[$id];
    }
    return null;
  }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value, filtered by the given array
   *
   * @param array        $filters Attributes to match during filtration
   *   e.g. array('category' => 'other')
   * @param string       $key     Name of attribute to make array keys
   * @param string|array $value   Name(s) of attribute to make array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
  public function getFilteredMemberList(
    array $filters = [],
    $key   = 'id',
    $value = 'name'
  ) {
    $members     = $this->models;
    $member_list = [];

    $values = $value;
    if (!is_array($values)) {
      $values = [$value,];
    }
    foreach ($members as $member) {
      $member_list[$member->get($key)] = [];
      foreach ($values as $item) {
        $member_list[$member->get($key)][$item] = $member->get($item);
      }
      if (count($member_list[$member->get($key)]) < 2) {
        $member_list[$member->get($key)] =
          array_pop($member_list[$member->get($key)]);
      }
      foreach ($filters as $attribute => $match_value) {
        if ($member->get($attribute) != $match_value) {
          unset($member_list[$member->get($key)]);
          break;
        }
      }

    }
    return $member_list;
  }

  /**
   * Checks whether the collection has a specific model
   *
   * @param string $id ID of the model to check for
   * @return boolean True if model exists, false otherwise
   */
  public function has($id) {
    $isset = isset($this->model[$id]);
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
   * Adds a model to this collection
   *
   * @param array $model_data  Data to feed into attributes of new model
   * @param array $arg_options Data to make properties of the new model
   * @return void
   */
  protected function add(array $model_data = [], array $arg_options = []) {
    $default_options = ['id' => $model_data->id, 'collection' => $this,];
    $options         = array_merge($default_options, $arg_options);

    $model_name = $this->collected_class;
    $model      = new $model_name($model_data, $options);

    $this->models[$model_data['id']] = $model;
  }

}
