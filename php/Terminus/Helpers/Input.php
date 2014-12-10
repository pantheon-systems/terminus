<?php
namespace Terminus\Helpers;

use \Terminus\User;

class Input {

  static public function environment($existing, $default, $message) {

    if (!$message) {
      $message = "Specify a environment";
    }

    if (!$env OR array_search($env, $envs) === false) {
      $env = \Terminus::menu( $envs , null, $message );
      $env = $envs[$env];
    }

    if (!$env) {
      \Terminus::error("Environment '%s' unavailable", array($env));
    }

    return $env;

  }

  public static function orglist() {
    $orgs = array('-'=>'None');
    $user = new User;
    foreach ($user->organizations() as $id => $org) {
      $orgs[$id] = $org->name;
    }
    return $orgs;
  }

}
