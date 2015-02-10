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

  public static function orglist($site=null) {
    $orgs = array('-'=>'None');
    $user = new User;
    foreach ($user->organizations() as $id => $org) {
      $orgs[$id] = $org->name;
    }
    return $orgs;
  }

  public static function orgname($args, $key, $default=null) {
    $orglist = Input::orglist();
    if (isset($args[$key])) {
      // if org id is sent fetch the name
      if (array_key_exists($args[$key], $orglist)) {
        return $orglist[$args[$key]];
      }
      return $args[$key];
    }
    $org = \Terminus::menu($orglist, false, "Choose organization");
    return $orglist[$org];
  }

  public static function orgid($args, $key, $default=null) {
    $orglist = Input::orglist();
    $flip = array_flip($orglist);
    if (isset($args[$key]) AND array_key_exists($args[$key], $flip)) {
      // if we have a valid name provided and we need the id
      return $flip[$args[$key]];
    } elseif(isset($args[$key]) AND  array_key_exists($args[$key],$orglist)) {
      return $args[$key];
    }

    $orglist = Input::orglist();
    $org = \Terminus::menu($orglist, false, "Choose organization");
    return $org;
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

  public static function env( $args = array(), $key = 'env', $label = 'Choose environment', $choices = null) {
    if (!$choices) {
      $choices = array('dev','test','live');
    }
    if ( isset($args[$key]) ) {
      return $args[$key];
    }

    return self::menu($choices, $default='dev', $label, true);

  }

  static function menu( $choices, $default = null, $text = "Select one", $return_value=false ) {
    echo PHP_EOL;
    $index = \cli\Streams::menu($choices,$default,$text);
    if ($return_value) {
      return $choices[$index];
    }
    return $index;
  }

  static function string( $args, $key, $label = "Enter") {
    if ( isset($args[$key]) ) {
      return $args[$key];
    }
    $string = \Terminus::prompt($label);
    return $string;
  }

  static function optional( $key, $args, $default = null ) {
    if (isset($args[$key])) {
      return $args[$key];
    } elseif ($default !== null) {
      return $default;
    }
    return $default;
  }
}
