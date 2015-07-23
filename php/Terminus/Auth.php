<?php
namespace Terminus;

use Terminus\FileCache;
use Terminus;
use Terminus\Session;

class Auth {

  public static function loggedIn() {
    if (Session::instance()->getValue('session', false) === false) {
      \Terminus::error("Please login first with `terminus auth login`");
    }
  }

}
