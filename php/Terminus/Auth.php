<?php
namespace Terminus;

use Terminus\Exceptions\TerminusException;
use Terminus\FileCache;
use Terminus;
use Terminus\Session;

class Auth {

  public static function loggedIn() {
    if (Session::instance()->getValue('session', false) === false) {
      throw new TerminusException("Please login first with `terminus auth login`");
    }
  }

}
