<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\Environment;
use \TerminusCommand;

class Environments {
  private $site;
  private $models = array();

  public function __construct($options = array()) {
    $this->site = $options['site'];

    return $this;
  }

  public function fetch() {
    $results = TerminusCommand::request("sites", $this->site->getId(), "environments", "GET");

    foreach (get_object_vars($results['data']) as $id => $environment_data) {
      $environment_data->id = $id;
      $this->models[$id] = new Environment($this->site, $environment_data);
    }

    return $this;
  }

  /**
   * List Environment IDs, with Dev/Test/Live first
   *
   */
  public function ids() {
    $ids = array_keys($this->models);

    # Reorder environments to put dev/test/live first
    $default_ids = array('dev', 'test', 'live');
    $multidev_ids = array_diff($ids, $default_ids);
    $ids = array_merge($default_ids, $multidev_ids);

    return $ids;
  }

  /**
   * Returns a list of all Multidev Environments
   *
   */
  public function multidev() {
    $environments = array_filter($this->all(), function($environment) {
      return $environment->isMultidev();
    });
    return $environments;
  }

  public function get($id) {
    $environment = array_key_exists($id, $this->models) ? $this->models[$id] : null;
    return $environment;
  }

  public function all() {
    $environments = array_values($this->models);
    return $environments;
  }
}
