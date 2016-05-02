<?php

namespace Terminus;

use Terminus\Caches\FileCache;
use Terminus\Models\User;

class Session {
  /**
   * @var Session
   */
  public static $instance;
  /**
   * @var FileCache
   */
  protected static $cache;
  /**
   * @var object
   */
  protected $data;

  /**
   * Instantiates object, sets session data
   */
  public function __construct() {
    self::$cache = new FileCache();
    $session     = self::$cache->getData('session');
    $this->data  = $session;
    if (empty($session)) {
      $this->data = new \stdClass();
    }

    self::$instance = $this;
  }

  /**
   * Removes the session from the cache
   *
   * @return void
   */
  public static function destroy() {
    self::$cache->remove('session');
  }

  /**
   * Returns given data property or default if DNE.
   *
   * @param string $key     Name of property to return
   * @param mixed  $default Default return value in case property DNE
   * @return mixed
   */
  public function get($key = 'session', $default = false) {
    if (isset($this->data) && isset($this->data->$key)) {
      return $this->data->$key;
    }
    return $default;
  }

  /**
   * Retrieves session data
   *
   * @return object
   */
  public static function getData() {
    $session = Session::instance();
    return $session->data;
  }

  /**
   * Returns session data indicated by the key
   *
   * @param string $key Name of session property to retrieve
   * @return mixed
   */
  public static function getValue($key) {
    $session          = Session::instance();
    $session_property = $session->get($key);
    return $session_property;
  }

  /**
   * Returns self, instantiating self if necessary
   *
   * @return Session
   */
  public static function instance() {
    if (!self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Sets a keyed value to be part of the data property object
   *
   * @param string $key   Name of data property
   * @param mixed  $value Value of property to set
   * @return Session
   */
  public function set($key, $value = null) {
    $this->data->$key = $value;
    return $this;
  }

  /**
   * Saves session data to cache
   *
   * @param array $data Session data to save
   * @return void|bool
   */
  public static function setData($data) {
    if (empty($data)) {
      return false;
    }
    $cache = new FileCache();
    $cache->putData('session', $data);
    $session = self::instance();
    $session->set('data', $data);
    foreach ($data as $k => $v) {
      $session->set($k, $v);
    }
    return true;
  }

  /**
   * Returns a user with the current session user id
   *
   * @return [user] $session user
   */
  public static function getUser() {
    $user_uuid = Session::getValue('user_uuid');
    $user      = new User((object)array('id' => $user_uuid));
    return $user;
  }

}
