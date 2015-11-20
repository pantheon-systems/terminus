<?php

namespace Terminus\Helpers;

use Terminus;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\User;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Collections\Upstreams;

/**
 * Helper class to handle inputs
 */
class Input {
  public static $NULL_INPUTS = array('', 'false', 'None', 'Null', '0');

  /**
   * Produces a menu to select a backup
   *
   * @param [array] $arg_options Elements as follows:
   *        [string] label         Prompt for STDOUT
   *        [array]  backups       Array of stdClass objs representing backups
   *        [array]  target_backup For STDERR, if necessary. As follows:
   *          [string] site Name of the site we want a backup from
   *          [string] env  Name of the environment we want a backup from
   * @return [stdClass] $target_backup An object representing the backup desired
   */
  public static function backup(array $arg_options = array()) {
    $default_options = array(
      'label'   => 'Select a backup',
      'backups' => array(),
      'context' => array(),
    );
    $options         = array_merge($default_options, $arg_options);
    $backups         = $options['backups'];
    if (empty($options['backups'])) {
      $command = 'terminus site backup create --site=<site> --env=<env>`';
      throw new TerminusException(
        'No backups available. Create one with `{command}`',
        array_merge($backups['context'], compact('command')),
        1
      );
    }

    $choices = array();
    foreach ($backups as $folder => $backup) {
      if (!isset($backup->filename)) {
        unset($backups[$folder]);
        continue;
      }
      if (!isset($backup->folder)) {
        $backup->folder = $folder;
      }
      $choices[] = $backup->filename;
    }
    $backups       = array_values($backups);
    $target_backup = $backups[self::menu($choices, null, $options['label'])];

    return $target_backup;
  }

  /**
   * Produces a menu to narrow down an element selection
   *
   * @param [array] $arg_options Elements as follows:
   *        [array]  args    Arguments given via param
   *        [string] key     Args key to search for
   *        [string] label   Prompt for STDOUT
   *        [array]  choices Menu options for the user
   * @return [string] Either the selection, its index, or the default
   */
  public static function backupElement(array $arg_options = array()) {
    $default_options = array(
      'args'    => array(),
      'key'     => 'element',
      'label'   => 'Select backup element',
      'choices' => array('code', 'database', 'files'),
    );
    $options         = array_merge($default_options, $arg_options);

    $args    = $options['args'];
    $key     = $options['key'];
    $choices = $options['choices'];
    if (isset($args[$key])) {
      if ($args[$key] == 'db') {
        return 'database';
      }
      if (in_array($args[$key], $options['choices'])) {
        return $args[$key];
      }
      throw new TerminusException(
        '{element} is an invalid element. Please select from these: {choices}',
        array('element' => $args[$key], 'choices' => implode(', ', $choices)),
        1
      );
    }

    $element = self::menu($choices, null, $options['label'], true);
    return $element;
  }

  /**
   * Produces a menu with the given attributes
   *
   * @param [array] $arg_options Arguments as follows:
   *        [array]  args    Arguments given via param
   *        [string] key     Args key to search for
   *        [string] label   Prompt for STDOUT
   *        [array]  choices Menu options for the user, may be a collection
   *        [Site]   site    Site object to gather environment choices from
   * @return [string] Either the selection, its index, or the default
   */
  public static function env(array $arg_options = array()) {
    $default_options = array(
      'args'    => array(),
      'key'     => 'env',
      'label'   => 'Choose environment',
      'choices' => array('dev', 'test', 'live'),
      'site'    => null,
    );
    $options         = array_merge($default_options, $arg_options);
    if (isset($options['args'][$options['key']])) {
      return $options['args'][$options['key']];
    }
    if (in_array($options['key'], array('env', 'from-env'))) {
      if (isset($_SERVER['TERMINUS_ENV'])) {
        return $_SERVER['TERMINUS_ENV'];
      }
    }
    $choices = $options['choices'];
    if (get_class($options['site']) == 'Site') {
      $choices = $options['site']->environments->ids();
    }

    $menu = self::menu(
      $choices,
      $default = 'dev',
      $options['label'],
      true
    );
    return $menu;
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
    if (count($choices) == 1) {
      $only_choice = array_shift($choices);
      return $only_choice;
    }
    $index = \cli\Streams::menu($choices, $default, $text);
    if ($return_value) {
      return $choices[$index];
    }
    return $index;
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
    if (isset($args[$key])) {
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
   * @param [array]  $options Options to feed into the orglist function
   * @return [string] ID of selected organization
  */
  public static function orgid(
    $args,
    $key = 'org',
    $default = null,
    $options = array()
  ) {
    $arguments = $args;
    if (!isset($arguments[$key]) && isset($_SERVER['TERMINUS_ORG'])) {
      $arguments[$key] = $_SERVER['TERMINUS_ORG'];
    }

    $orglist = Input::orglist($options);
    $flip    = array_flip($orglist);
    if (isset($arguments[$key])) {
      if (isset($flip[$arguments[$key]])) {
        return $flip[$arguments[$key]];
      } elseif (isset($orglist[$arguments[$key]])) {
        return $arguments[$key];
      } elseif (in_array($arguments[$key], self::$NULL_INPUTS)
        || !empty($arguments)
      ) {
        return $default;
      }
    }

    // include the Org ID in the output menu
    $orglist_with_id = array();
    foreach ($orglist as $id => $name) {
      if ($name == 'None') {
        $orglist_with_id[$id] = $name;
        continue;
      }
      $orglist_with_id[$id] = sprintf("%s (%s)", $name, $id);
    }

    $org = Terminus::menu($orglist_with_id, false, "Choose organization");
    if ($org == '-') {
      return $default;
    }
    return $org;
  }

  /**
   * Returns an array listing organizaitions applicable to user
   *
   * @param [array] $options Elements as follows:
   *        [boolean] allow_none True to allow the "none" option
   * @return [array] $orgs A list of organizations
  */
  public static function orglist($options = array()) {
    $orgs = array();

    if (!isset($options['allow_none']) || (boolean)$options['allow_none']) {
      $orgs = array('-' => 'None');
    }

    $user = new User();
    foreach ($user->organizations->all() as $id => $org) {
      $org_data = $org->get('organization');
      $orgs[$org->get('id')] = $org_data->profile->name;
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
    if (isset($args[$key])) {
      //If org id is sent, fetch the name
      if (array_key_exists($args[$key], $orglist)) {
        return $orglist[$args[$key]];
      }
      return $args[$key];
    }
    $org = Terminus::menu($orglist, false, "Choose organization");
    return $orglist[$org];
  }

  /**
   * Helper function to get role
   *
   * @param [array]  $assoc_args Argument array passed from commands
   * @param [string] $message    Prompt to STDOUT
   * @return [string] $role Name of role
   */
  static public function role(
    $assoc_args,
    $message = 'Select a role for this member'
  ) {
    $roles = array('developer', 'team_member', 'admin');
    if (!isset($assoc_args['role'])
      || !in_array(strtolower($assoc_args['role']), $roles)
    ) {
      $role = strtolower(
        $roles[Input::menu(
          $roles,
          null,
          $message
        )]
      );
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
  public static function sitename(
    $args = array(),
    $key = 'site',
    $label = 'Choose site'
  ) {
    // return early if sitename is provided in args
    if (isset($args[$key])) {
      return $args[$key];
    }
    if (isset($_SERVER['TERMINUS_SITE'])) {
      return $_SERVER['TERMINUS_SITE'];
    }
    $sites     = new Sites();
    $sites     = $sites->all();
    $sitenames = array_map(
      function($site) {
        $site_name = $site->get('name');
        return $site_name;
      }, $sites
    );

    $choices = array();
    foreach ($sitenames as $sitename) {
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
    if (isset($args[$key])) {
      return $args[$key];
    }
    if (Terminus::getConfig('format') != 'normal') {
      return $default;
    }
    $string = Terminus::prompt($label);
    if ($string == '') {
      return $default;
    }
    return $string;
  }

  /**
   * Helper function to select valid upstream
   *
   * @param [array]   $args Args to parse value from
   * @param [string]  $key  Index to search for in args
   * @param [boolean] $exit If true, throw error when no value is found
   *
   * @return [Upstream] $upstream
   */
  public static function upstream($args, $key, $exit = true) {
    $upstreams = new Upstreams();
    if (isset($args[$key])) {
      $upstream = $upstreams->getByIdOrName($args[$key]);
      if ($upstream == null) {
        throw new TerminusException(
          'Could not find upstream: {upstream}',
          array('upstream' => $args['upstream']),
          (integer)$exit
        );
      }
    } else {
      $upstream = $upstreams->get(
        Terminus::menu($upstreams->getMemberList('id', 'longname'))
      );
    }
    return $upstream;
  }

  /**
   * Helper function to select Site Workflow
   *
   * @param [Site]   $site Site from which to fetch workflows
   * @param [array]  $args Args to parse value from
   * @param [string] $key  Index to search for in args
   *
   * @return [Workflow] $workflow
   */
  public static function workflow($site, $args, $key = 'workflow_id') {
    if (isset($args['workflow_id'])) {
      $workflow_id = $args[$key];
    } else {
      // Only retrieve the most-recent 100 workflows
      $site->workflows->fetch(array('paged' => false));

      $workflow_menu_args = array();
      foreach ($site->workflows->all() as $workflow) {
        if ($workflow->get('environment')) {
          $environment = $workflow->get('environment');
        } else {
          $environment = 'None';
        }

        $created_at = date('Y-m-d H:i:s', $workflow->get('created_at'));

        $workflow_description = sprintf(
          "%s - %s - %s - %s",
          $environment,
          $workflow->get('description'),
          $created_at,
          $workflow->id
        );
        $workflow_menu_args[$workflow->id] = $workflow_description;
      }
      $workflow_id = Input::menu(
        $workflow_menu_args,
        null,
        'Choose workflow'
      );
    }

    $workflow = $site->workflows->get($workflow_id);
    return $workflow;
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
    if (Terminus::getConfig('yes')) {
      return true;
    }
    $question = vsprintf($question, $params);
    fwrite(STDOUT, $question . " [y/n] ");

    $answer = trim(fgets(STDIN));

    $is_yes = (boolean)($answer == 'y');
    return $is_yes;
  }

}
