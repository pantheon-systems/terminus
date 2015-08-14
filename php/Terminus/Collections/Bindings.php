<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\Models\Binding;
use \TerminusCommand;

class Bindings {
  private $environment;
  private $models = array();

  public function __construct($options = array()) {
    $this->environment = $options['environment'];

    return $this;
  }

  public function fetch() {
    $results = TerminusCommand::request("sites", $this->environment->site->getId(), "bindings", "GET");

    foreach (get_object_vars($results['data']) as $id => $binding_data) {
      # Only include bindings for this environment
      if ($binding_data->environment == $this->environment->id) {
        $binding_data->id = $id;
        $this->models[$id] = new Binding($binding_data, array('collection' => $this));
      }
    }

    return $this;
  }

  public function get($id) {
    return array_key_exists($id, $this->models) ? $this->models[$id] : null;
  }

  public function all() {
    return array_values($this->models);
  }
}
