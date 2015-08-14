<?php

namespace Terminus\Collections;
use \TerminusCommand;
use \Terminus\Models\Workflow;

class Workflows {
  private $models = array();

  public $owner;
  public $owner_type;

  public function __construct($options = array()) {
    $this->owner = $options['owner'];
    $this->owner_type = $options['owner_type'];

    return $this;
  }

  public function url($options = array()) {
    switch ($this->owner_type) {
      case 'user':
        return sprintf("users/%s/workflows", $this->owner->id);
      case 'site':
        if (isset($options['environment'])) {
          return sprintf("sites/%s/environments/%s/workflows", $this->owner->id, $options['environment']);
        } else {
          return sprintf("sites/%s/workflows", $this->owner->id);
        }
      case 'organization':
        return sprintf("users/%s/organizations/%s/workflows", $this->owner->user->id, $this->owner->id);
    }
  }

  public function create($type, $options = array()) {
    if (isset($options['environment'])) {
      $url = $this->url(array('environment' => $options['environment']));
    } else {
      $url = $this->url();
    }

    if (!isset($options['params'])) {
      $options['params'] = array();
    }

    $results = TerminusCommand::simple_request($url, array(
      'method' => 'post',
      'data' => array(
        'type' => $type,
        'params' => (object) $options['params']
      )
    ));

    $model = new Workflow($results['data'], array(
      'owner' => $this->owner,
      'owner_type' => $this->owner_type,
    ));
    $this->add($model);
    return $model;
  }

  public function fetch() {
    $results = TerminusCommand::simple_request($this->url());

    foreach ($results['data'] as $model_data) {
      $model = new Workflow($model_data, array(
        'owner' => $this->owner,
        'owner_type' => $this->owner_type,
      ));
      $this->add($model);
    }

    return $this;
  }

  public function add($model) {
    $model->collection = $this;
    $this->models[$model->id] = $model;
  }

  public function get($id) {
    return array_key_exists($id, $this->models) ? $this->models[$id] : null;
  }

  public function all() {
    return array_values($this->models);
  }
}
