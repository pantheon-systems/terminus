<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\User;
use Terminus\Instrument;
use \Terminus_Command;

class Instruments {
  private $_user;
  private $_models = array();

  public function __construct($user = false, $options = array()) {
    $this->_user = $user;
    if(!$user) {
      $this->_user = new User();
    }
    return $this;
  }

  public function fetch() {
    $results = Terminus_Command::request("users", $this->_user->getId(), "instruments", "GET");

    foreach (get_object_vars($results['data']) as $id => $instrument_data) {
      $instrument_data->id = $id;
      $this->_models[$id] = new Instrument($this->_user, $instrument_data);
    }

    return $this;
  }

  /**
   * List Instrument IDs
   *
   * @return [array] $ids Array of IDs
   */

  public function ids() {
    $ids = array_keys($this->_models);
    return $ids;
  }

  public function get($id) {
    return array_key_exists($id, $this->_models) ? $this->_models[$id] : null;
  }

  public function all() {
    return array_values($this->_models);
  }
}
