<?php
namespace Terminus\Helpers;

use \Terminus\User;
use \Terminus\SiteFactory;

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

  /**
   * Input helper that provides interactive site list
   *
   * @param $args array -- The args passed in from argv
  */
  public static function site( $args = array(), $key = 'site', $label = 'Choose site') {
      // early return if a valid site has been offered
      if ( isset($args[$key]) ) {
        if ( $site = SiteFactory::instance($args[$key]) ) {
          return $site->getName();
        }
      }

      $sites = SiteFactory::instance();
      $choices = array();
      foreach( $sites as $site ) {
        $choices[$site->information->name] = $site->information->name;
      }
      return self::menu($choices, $default = null, $label);
  }

  public static function env( $args = array(), $key = 'env', $label = 'Choose environment') {
    $available = array('live','dev','test');
    if ( isset($args[$key]) and in_array($args[$key], $available)) {
      return $args[$key];
    }

    return self::menu($available, $default='dev', "Select $label", true);

  }

  static function menu( $choices, $default = null, $text = "Select one", $return_value=false ) {
    echo PHP_EOL;
    $index = \cli\Streams::menu($choices,$default,$text);
    if ($return_value) {
      return $choices[$index];
    }
    return $index;
  }
}
