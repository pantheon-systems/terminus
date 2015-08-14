<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\Models\Binding;
use \Terminus_Command;

class Bindings {
  private $environment;
  private $models = array();

  public function __construct($options = array()) {
    $this->environment = $options['environment'];

    return $this;
  }

  public function fetch() {
    $results = Terminus_Command::request("sites", $this->environment->site->getId(), "bindings", "GET");

    foreach (get_object_vars($results['data']) as $id => $binding_data) {
      # Only include bindings for this environment
      if ($binding_data->environment == $this->environment->id) {
        $binding_data->id = $id;
        $this->models[$id] = new Binding($binding_data, array(
          'environment' => $this->environment,
          'collection' => $this
        ));
      }
    }

    return $this;
  }

  /**
   * Get bindings by type (e.g. "appserver", "dbserver", etc)
   *
   */
  public function getByType($type) {
    $models = array_filter($this->all(), function($binding) use ($type) {
      return (
        $binding->get('type') == $type
        && !$binding->get('failover')
        && !$binding->get('slave_of')
      );
    });

    $models = array_values($models);
    return $models;
  }

  public function get($id) {
    return array_key_exists($id, $this->models) ? $this->models[$id] : null;
  }

  public function all() {
    return array_values($this->models);
  }
}
