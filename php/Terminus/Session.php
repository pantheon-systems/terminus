<?php
namespace Terminus;

use Terminus\FileCache;
use Terminus;

class Session {
  static $instance;
  private $session;
  private $user_uuid;
  private $session_expires_time;
  private $email;

  public function __construct( $session = null ) {
    $cache = Terminus::get_cache();

    if( !$session ) {
      $session = $cache->get_data('session');
    } else {
      $cache->put_data('session',$session);
    }

    $this->session = $session->session;
    $this->user_uuid = $session->user_uuid;
    $this->session_expires_time = $session->session_expires_time;
    $this->email = $session->email;
    self::$instance = $this;
    return $this;
  }

  public function get($key = 'session', $default = false) {
    return $this->$key;
  }

  public function set($key, $value) {
    $this->$key = $value;
    return $this;
  }

  public static function instance( $session = null ) {
    if (!self::$instance)
      self::$instance = new Self($session);
    return self::$instance;
  }

  public static function getData( $key, $default = false ) {
    $session = Session::instance();
    return $session->get($key);
  }

}
