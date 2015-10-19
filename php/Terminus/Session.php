<?php

namespace Terminus;

use Terminus;

class Session {
  static $instance;
  protected $data;

  /**
   * Instantiates object, sets session data
   *
   * @return [Session] $this
   */
  public function __construct() {
    $cache   = Terminus::getCache();
    $session = $cache->get_data('session');

    $this->data = $session;
    if (empty($session)) {
      $this->data = new \stdClass();
    }

    self::$instance = $this;
    return $this;
  }

  /**
   * Retruns given data property or default if DNE.
   *
   * @param [string] $key     Name of property to return
   * @param [mixed]  $default Default return value in case property DNE
   * @return [mixed] $this->data->$key or $default
   */
  public function get($key = 'session', $default = false) {
    if (isset($this->data) && isset($this->data->$key)) {
      return $this->data->$key;
    }
    return $default;
  }

  /**
   * Sets a keyed value to be part of the data property object
   *
   * @param [string] $key   Name of data property
   * @param [mixed]  $value Value of property to set
   * @return [Session] $this
   */
  public function set($key, $value) {
    $this->data->$key = null;
    if (!empty($value)) {
      $this->data->$key = $value;
    }
    return $this;
  }

  /**
   * Retrieves session data
   *
   * @return [array] $data
   */
  public static function getData() {
    $session = Session::instance();
    return $session->data;
  }

  /**
   * Returnes session data indicated by the key
   *
   * @param [string] $key Name of session property to retrieve
   * @return [mixed] $session_property
   */
  public static function getValue($key) {
    $session          = Session::instance();
    $session_property = $session->get($key);
    return $session_property;
  }

  /**
   * Returns self, instantiating self if necessary
   *
   * @return [Session] self::$instance Static version of $this
   */
  public static function instance() {
    if (!self::$instance) {
      self::$instance = new self($session);
    }
    return self::$instance;
  }

  /**
   * Saves session data to cache
   *
   * @param [array] $data Session data to save
   * @return [void]
   */
  public static function setData($data) {
    $cache = Terminus::getCache();
    Terminus::getLogger()->info('Saving session data');
    $cache->put_data('session', $data);
    $session = self::instance();
    $session->set('data', $data);
    if (empty($data)) {
      return false;
    }
    foreach ($data as $k => $v) {
      $session->set($k, $v);
    }
  }

}
