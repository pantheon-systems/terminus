<?php
namespace Terminus\Helpers;

use \Terminus\User;
use \Terminus\SiteFactory;
use \Terminus\SitesCache;
use \Terminus\Products;

/**
 * Helper class to handle inputs
 */
class Input {
  public static $NULL_INPUTS = array('', 'false', 'None', 'Null', '0');

  /**
   * Produces a menu with the given attributes
   *
   * @param [array]  $args    Arguments given via param
   * @param [string] $key     Args key to search for
   * @param [string] $label   Prompt for STDOUT
   * @param [array]  $choices Menu options for the user
   * @return [string] Either the selection, its index, or the default
   */
  public static function env(
      $args = array(),
      $key = 'env',
      $label = 'Choose environment',
      $choices = null
  ) {
    if(!$choices) {
      $choices = array('dev', 'test', 'live');
    }
    if (isset($args[$key])) {
      return $args[$key];
    }

    $menu = self::menu($choices, $default = 'dev', $label, true);
    return $menu;
  }

  /**
   * Helper function to get environment name
   *
   * @param [string] $message Prompt to STDOUT
   * @return [string] $env Name of environment to work on
   */
  static public function environment($message = 'Specify an environment') {
    if(!$env || (array_search($env, $envs) === false)) {
      $env = \Terminus::menu($envs, null, $message);
      $env = $envs[$env];
    }
    if(!$env) {
      \Terminus::error("Environment '%s' unavailable", array($env));
    }

    return $env;
  }

  /**
   * Produces a menu with the given attributes
   *
   * @param [array]   $choices      Menu options for the user
   * @param [mixed]   $default      Given as null option in the menu
   * @param [string]  $text         Prompt printed to STDOUT
   * @param [boolean] $return_value If true, returns selection. False, the index
   * @return [string] Either the selection, its index, or the default
   */
  public static function menu(
      $choices,
      $default = null,
      $text = "Select one",
      $return_value = false
  ) {
    echo PHP_EOL;
    $index = \cli\Streams::menu($choices, $default, $text);
    if ($return_value) {
      return $choices[$index];
    }
    return $index;
  }

  /**
   * Helper function to select valid product
   *
   * @param [array]   $args Args to parse value from
   * @param [string]  $key  Index to search for in args
   * @param [boolean] $exit If true, throw error when no value is found
   *
   * @return [array] $product
   */
  public static function product($args, $key, $exit = true) {
    if(isset($args[$key])) {
      $product = Products::getByIdOrName($args[$key]);
      if(!$product) {
        \Terminus::error("Couldn't find product: %s", array($args['product']));
      }
    } else {
      $product = \Terminus::menu(Products::selectList());
      $product = Products::getByIndex($product);
    }
    if(!$product AND $exit) {
      \Terminus::error("Product is required.");
    }
    return $product;
  }

  /**
   * Returns $args[$key] if exists, $default otherwise
   *
   * @param [string] $key     Index of arg to return
   * @param [array]  $args    Args to search for key
   * @param [mixed]  $default Returned if $args[$key] DNE
   * @return [mixed] Either $args[$key] or $default
   */
  public static function optional($key, $args, $default = null) {
    if(isset($args[$key])) {
      return $args[$key];
    }
    return $default;
  }

  /**
   * Input helper that provides interactive menu to select org name
   *
   * @param [array]  $args    The args passed in from argv
   * @param [string] $key     Args key to search for
   * @param [string] $default Returned if arg and stdin fail in interactive
   * @return [string] ID of selected organization
  */
  public static function orgid($args, $key, $default = null, $options = array()) {
    $allow_none = isset($options['allow_none']) ? $options['allow_none'] : true;

    $orglist = Input::orglist();
    $flip    = array_flip($orglist);
    if(isset($args[$key]) && array_key_exists($args[$key], $flip)) {
      // if we have a valid name provided and we need the id
      return $flip[$args[$key]];
    } elseif(isset($args[$key]) && array_key_exists($args[$key], $orglist)) {
      return $args[$key];
    } elseif(
      isset($args[$key])
      && in_array($args[$key], self::$NULL_INPUTS)
      || !empty($args)
    ) {
      return $default;
    }

    $orglist = Input::orglist(array('allow_none' => $allow_none));

    // include the Org ID in the output menu
    $orglist_with_id = array();
    foreach ($orglist as $id => $name) {
      if ($name == 'None') {
        $orglist_with_id[$id] = $name;
        continue;
      }
      $orglist_with_id[$id] = sprintf("%s (%s)", $name, $id);
    }

    $org = \Terminus::menu($orglist_with_id, false, "Choose organization");
    if($org == '-') {
      return $default;
    }
    return $org;
  }

  /**
   * Returns an array listing organizaitions applicable to user
   *
   * @return [array] List of organizations
  */
  public static function orglist($options = array()) {
    $orgs = array();

    $allow_none = isset($options['allow_none']) ? $options['allow_none'] : true;
    if ($allow_none) {
      $orgs = array('-' => 'None');
    }

    $user = new User;
    foreach($user->organizations() as $id => $org) {
      $orgs[$id] = $org->name;
    }
    return $orgs;
  }

  /**
   * Input helper that provides interactive menu to select org name
   *
   * @param [array]  $args The args passed in from argv
   * @param [string] $key  Args key to search for
   * @return [string] Site name
  */
  public static function orgname($args, $key) {
    $orglist = Input::orglist();
    if(isset($args[$key])) {
      //If org id is sent, fetch the name
      if(array_key_exists($args[$key], $orglist)) {
        return $orglist[$args[$key]];
      }
      return $args[$key];
    }
    $org = \Terminus::menu($orglist, false, "Choose organization");
    return $orglist[$org];
  }

  /**
   * Helper function to get role
   *
   * @param [array]  $assoc_args Argument array passed from commands
   * @param [string] $message Prompt to STDOUT
   * @return [string] $role Name of role
   */
  static public function role($assoc_args, $message = 'Select a role for this member') {
    $roles = array('developer', 'team_member', 'admin');
    if(
      !isset($assoc_args['role'])
      || !in_array(strtolower($assoc_args['role']), $roles)
    ) {
      $role = strtolower($roles[Input::menu(
        $roles,
        null,
        'Select a role for the new member'
      )]);
    } else {
      $role = $assoc_args['role'];
    }
    return $role;
  }

  /**
   * Input helper that provides interactive site list
   *
   * @param [array]  $args  The args passed in from argv
   * @param [string] $key   Args key to search for
   * @param [string] $label Prompt for STDOUT
   * @return [string] Site name
  */
  public static function sitename($args = array(), $key = 'site', $label = 'Choose site') {
    // return early if sitename is provided in args
    if(isset($args[$key])) {
      return $args[$key];
    }
    $sitesCache = new SitesCache();
    $sitenames = array_map(function($site_cache) {
      return $site_cache['name'];
    }, $sitesCache->all());

    $choices = array();
    foreach($sitenames as $sitename) {
      $choices[$sitename] = $sitename;
    }
    $menu = self::menu($choices, $default = null, $label);
    return $menu;
  }

  /**
   * Returns $args[key] if exists, then STDIN, then $default
   *
   * @param [array]  $args    Args already input
   * @param [string] $key     Key for searched-for argument
   * @param [string] $label   Promp printed to STDOUT
   * @param [mixed]  $default Returns if no other choice
   *
   * @return [string] Either $args[$key]. $default, or string from prompt
   */
  public static function string(
      $args,
      $key,
      $label = "Enter",
      $default = null
  ) {
    if(isset($args[$key])) {
      return $args[$key];
    }
    $string = \Terminus::prompt($label);
    if(($string == '') && isset($default)) {
      return $default;
    }
    return $string;
  }

  /**
   * Same as confirm but doesn't exit
   *
   * @param [string] $question Question to ask
   * @param [array]  $params   Args for vsprintf()
   *
   * @return [boolean] $is_yes
   */
  public static function yesno($question, $params = array()) {
    if(\Terminus::get_config('yes')) {
      return true;
    }
    $question = vsprintf($question, $params);
    fwrite(STDOUT, $question . " [y/n] ");

    $answer = trim(fgets(STDIN));

    $is_yes = (boolean)($answer == 'y');
    return $is_yes;

  }
}
