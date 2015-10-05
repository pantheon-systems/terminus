<?php

namespace Terminus;

use Terminus\Session;
use Terminus\Exceptions\TerminusException;

class Auth {

  public static function loggedIn() {
    if (Session::instance()->getValue('session', false) === false) {
      throw new TerminusException("Please login first with `terminus auth login`");
    }
  }

}
