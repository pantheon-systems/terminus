<?php

namespace Terminus\Models\Collections;

use Terminus\Request;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\TerminusModel;

abstract class TerminusCollection extends TerminusModel {
  /**
   * @var TerminusModel[]
   */
  protected $models = array();

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param array $options To be set to $this->key
   */
  public function __construct(array $options = array()) {
    foreach ($options as $key => $option) {
      $this->$key = $option;
    }
    $this->request = new Request();
  }

  /**
   * Retrieves all models
   *
   * @return TerminusModel[]
   */
  public function all() {
    $models = array_values($this->getMembers());
    return $models;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options params to pass to url request
   * @return TerminusCollection $this
   */
  public function fetch(array $options = array()) {
    $results = $this->getCollectionData($options);
    $data    = $this->objectify($results['data']);

    foreach (get_object_vars($data) as $id => $model_data) {
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
   * @throws TerminusException
   */
  public function get($id) {
    $models = $this->getMembers();
    if (isset($models[$id])) {
      return $models[$id];
    }
    $model = explode('\\', $this->getMemberName());
    throw new TerminusException(
      'Could not find {model} "{id}"',
      array(
        'model' => strtolower(array_pop($model)),
        'id'    => $id,
      ),
      1
    );
  }

  /**
   * List Model IDs
   *
   * @return string[] Array of all model IDs
   */
  public function ids() {
    $models = $this->getMembers();
    $ids    = array_keys($models);
    return $ids;
  }

  /**
   * Adds a model to this collection
   *
   * @param object $model_data Data to feed into attributes of new model
   * @param array  $options    Data to make properties of the new model
   * @return TerminusModel
   */
  public function add($model_data, array $options = array()) {
    $model   = $this->getMemberName();
    $owner   = $this->getOwnerName();
    $options = array_merge(
      array(
        'id'         => $model_data->id,
        'collection' => $this,
      ),
      $options
    );

    if ($owner) {
      if (isset($this->$owner)) {
        $options[$owner] = $this->$owner;
      } else {
        $options[$owner] = $this->owner;
      }
    }

    $model = new $model(
      $model_data,
      $options
    );

    $this->models[$model_data->id] = $model;
    return $model;
  }

  /**
   * Gives the name of this class
   *
   * @return string
   */
  protected function getClassName() {
    $class_name = get_class($this);
    return $class_name;
  }

  /**
   * Retrieves collection data from the API
   *
   * @param array $options params to pass to url request
   * @return array
   */
  protected function getCollectionData($options = array()) {
    $function_name = 'request';
    if (isset($options['paged']) && $options['paged']) {
      $function_name = 'pagedRequest';
    }

    $fetch_args = array();
    if (isset($options['fetch_args'])) {
      $fetch_args = $options['fetch_args'];
    }
    $options = array_merge(
      array('options' => array('method' => 'get')),
      $this->getFetchArgs(),
      $fetch_args
    );
    $results = $this->request->$function_name(
      $this->getFetchUrl(),
      $options
    );
    return $results;
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
    array $filters,
    $key   = 'id',
    $value = 'name'
  ) {
    $members     = $this->getMembers();
    $member_list = array();

    $values = $value;
    if (!is_array($values)) {
      $values = array($value);
    }
    foreach ($members as $member) {
      $member_list[$member->get($key)] = array();
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
   * Names the model-owner of this collection, false if DNE
   *
   * @return string|bool
   */
  protected function getOwnerName() {
    return false;
  }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value
   *
   * @param string $key   Name of attribute to make array keys
   * @param string $value Name of attribute to make array values
   * @return array Array rendered as requested
   *         $this->attribute->$key = $this->attribute->$value
   */
  public function getMemberList($key = 'id', $value = 'name') {
    $member_list = $this->getFilteredMemberList(array(), $key, $value);
    return $member_list;
  }

  /**
   * Returns class name of the model collected by this collection.
   *
   * @return string Name of model
   */
  protected function getMemberName() {
    $name_array = explode('\\', get_class($this));
    $model_name = $name_array[0]
      . '\\' . $name_array[1]
      . '\\' . substr(array_pop($name_array), 0, -1);
    return $model_name;
  }

  /**
   * Retrieves all members of this collection
   *
   * @return TerminusModel[]
   */
  protected function getMembers() {
    if (empty($this->models)) {
      $this->fetch();
    }
    return $this->models;
  }

  /**
   * Turns an associative array into a stdClass object
   *
   * @param mixed $array Array to turn into object
   * @return \stdClass
   */
  protected function objectify($array = array()) {
    if (is_array($array)) {
      $object = new \stdClass();
      foreach ($array as $key => $value) {
        $object->$key = $value;
      }
      return $object;
    }
    return $array;
  }

}
