<?php
namespace Terminus;

use Terminus\FileCache;
use Terminus;
use Terminus\Session;

class Auth {

  public static function loggedIn() {
    if ( false === Session::instance()->getValue('session',false) AND !Terminus::is_test() ) {
      \Terminus::error("Please login first with `terminus auth login`");
    }
  }

}
