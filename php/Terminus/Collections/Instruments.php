<?php

namespace Terminus\Collections;
use Terminus\Request;
use Terminus\User;
use Terminus\Instrument;
use \Terminus_Command;

class Instruments {
  private $user;
  private $models = array();

  public function __construct($options = array()) {
    $this->user = $options['user'];
  }

  public function fetch() {
    $results = Terminus_Command::request(
      'users',
      $this->user->get('id'),
      'instruments',
      'GET'
    );

    foreach(get_object_vars($results['data']) as $id => $instrument_data) {
      $instrument_data->id = $id;
      $options             = array('user' => $this->user);
      $this->models[$id]   = new Instrument(
        $instrument_data,
        $options
      );
    }

    return $this;
  }

  /**
   * List Instrument IDs
   *
   * @return [array] $ids Array of IDs
   */

  public function ids() {
    $ids = array_keys($this->models);
    return $ids;
  }

  public function get($id) {
    return array_key_exists($id, $this->models) ? $this->models[$id] : null;
  }

  public function all() {
    return array_values($this->models);
  }
}
