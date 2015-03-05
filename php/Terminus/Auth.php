<?php
namespace Terminus;

use Terminus;
use Terminus\FileCache;
use Terminus\Session;

class Auth {

  public static function loggedIn() {
    if ( false === Session::instance()->getValue('session',false) AND !Terminus::is_test() ) {
      \Terminus::error("Please login first with `terminus auth login`");
    }
  }

  public static function login($email, $password) {
    $options = array(
        'body' => json_encode(array(
          'email' => $email,
          'password' => $password,
        )),
        'headers' => array('Content-type'=>'application/json'),
    );

    $response = \Terminus_Command::request('login','','','POST',$options);

    // Prepare credentials for storage.
    $data = array(
      'user_uuid' => $response['data']->user_id,
      'session' => $response['data']->session,
      'session_expire_time' => $response['data']->expires_at,
      'email' => $email,
    );

    // creates a session instance
    Session::instance()->setData($data);
  }

  public static function logout() {
    Session::destroy();
  }
}
