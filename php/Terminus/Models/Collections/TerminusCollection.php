<?php

namespace Terminus\Models\Collections;

use TerminusCommand;
use Terminus\Models\TerminusModel;

abstract class TerminusCollection extends TerminusModel {
  protected $models = array();

  /**
   * Instantiates the collection, sets param members as properties
   *
   * @param [array] $options To be set to $this->key
   * @return [TerminusCollection] $this
   */
  public function __construct($options = array()) {
    foreach ($options as $key => $option) {
      $this->$key = $option;
    }
  }

  /**
   * Retrieves all models
   *
   * @return [array] $models
   */
  public function all() {
    $models = array_values($this->getMembers());
    return $models;
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [TerminusCollection] $this
   */
  public function fetch($paged = false) {
    $results = $this->getCollectionData($paged);
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
   * @param [string] $id ID of desired model instance
   * @return [TerminusModel] $this->models[$id]
   */
  public function get($id) {
    $models = $this->getMembers();
    if (isset($models[$id])) {
      return $models[$id];
    }
    return null;
  }

  /**
   * List Model IDs
   *
   * @return [array] $ids Array of all model IDs
   */
  public function ids() {
    $models = $this->getMembers();
    $ids    = array_keys($models);
    return $ids;
  }

  /**
   * Adds a model to this collection
   *
   * @param [stdClass] $model_data Data to feed into attributes of new model
   * @param [array]    $options    Data to make properties of the new model
   * @return [void]
   */
  protected function add($model_data, $options = array()) {
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
    $this->models[$model_data->id] = new $model(
      $model_data,
      $options
    );
  }

  /**
   * Gives the name of this class
   *
   * @return [string] $class_name
   */
  protected function getClassName() {
    $class_name = get_class($this);
    return $class_name;
  }

  /**
   * Retrieves collection data from the API
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [array] $results
   */
  protected function getCollectionData($paged = false) {
    $function_name = 'simpleRequest';
    if ($paged) {
      $function_name = 'pagedRequest';
    }

    $options = array_merge(
      array('options' => array('method' => 'get')),
      $this->getFetchArgs()
    );
    $results = TerminusCommand::$function_name(
      $this->getFetchUrl(),
      $options
    );
    return $results;
  }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value, filtered by the given array
   *
   * @param [array]  $filters Attributes to match during filtration
   *   e.g. array('category' => 'other')
   * @param [string] $key     Name of attribute to make array keys
   * @param [mixed]  $value   Name(s) of attribute to make array values
   * @return [array] $member_list Array rendered as requested
   *         [mixed] $this->attribute->$key = $this->attribute->$value
   */
  public function getFilteredMemberList(
    $filters,
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
   * @return [string|boolean] $owner_name
   */
  protected function getOwnerName() {
    return false;
  }

  /**
   * Returns an array of data where the keys are the attribute $key and the
   *   values are the attribute $value
   *
   * @param [string] $key   Name of attribute to make array keys
   * @param [string] $value Name of attribute to make array values
   * @return [array] $member_list Array rendered as requested
   *         [mixed] $this->attribute->$key = $this->attribute->$value
   */
  public function getMemberList($key = 'id', $value = 'name') {
    $member_list = $this->getFilteredMemberList(array(), $key, $value);
    return $member_list;
  }

  /**
   * Returns name of the model collected by this collection
   *
   * @return [string] $model_name Name of model
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
   * @return [array] $this->models
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
   * @param [array] $array Array to turn into object
   * @return [stdClass] $object
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
