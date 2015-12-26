<?php

namespace Terminus\Helpers;

use Terminus;
use Terminus\Utils;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Site;
use Terminus\Models\Upstream;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Collections\Upstreams;

/**
 * Helper class to handle inputs
 */
class Input {
  static $NULL_INPUTS = array('', 'false', 'None', 'Null', '0');

  /**
   * Produces a menu to select a backup
   *
   * @param array $arg_options Elements as follow:
   *        [string] label   Prompt for STDOUT
   *        [array]  backups Array of Backup objects
   * @return \stdClass An object representing the backup desired
   * @throws TerminusException
   */
  public static function backup(array $arg_options = array()) {
    $default_options = array(
      'label'   => 'Select a backup',
      'backups' => array(),
    );
    $options         = array_merge($default_options, $arg_options);
    $backups         = $options['backups'];
    if (empty($options['backups'])) {
      $command = 'terminus site backup create --site=<site> --env=<env>`';
      throw new TerminusException(
        'No backups available. Create one with `{command}`',
        compact('command'),
        1
      );
    }

    $choices = array();
    foreach ($backups as $folder => $backup) {
      if ($backup->get('filename') == null) {
        unset($backups[$folder]);
        continue;
      }
      $choices[] = $backup->get('filename');
    }
    $choice        = self::menu(
      array('choices' => $choices, 'message' => $options['label'])
    );
    $backups       = array_values($backups);
    $target_backup = $backups[$choice];

    return $target_backup;
  }

  /**
   * Produces a menu to narrow down an element selection
   *
   * @param array $arg_options Elements as follow:
   *        [array]  args    Arguments given via param
   *        [string] key     Args key to search for
   *        [string] label   Prompt for STDOUT
   *        [array]  choices Menu options for the user
   * @return string Either the selection, its index, or the default
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

    $element = self::menu(
      array(
        'choices' => $choices,
        'message' => $options['label'],
        'return_value' => true,
      )
    );
    return $element;
  }

  /**
   * Asks for confirmation before running a destructive operation.
   *
   * @param array $arg_options Elements as follow:
   *        string $question Prompt text
   *        array  $params   Elements to interpolate into the prompt text
   * @return bool True if prompt is accepted
   */
  static function confirm(array $arg_options = array()) {
    if (Terminus::getConfig('yes')) {
      return true;
    }
    $default_options = array(
      'message' => 'Do you want to continue?',
      'context' => array(),
    );
    $options         = array_merge($default_options, $arg_options);
    $question        = vsprintf($options['message'], $options['context']);
    fwrite(STDOUT, $question . ' [y/n] ');
    $answer = trim(fgets(STDIN));

    if ($answer != 'y') {
      exit(0);
    }
    return true;
  }

  /**
   * Facilitates the selection of a day of the week
   *
   * @param array $arg_options Elements as follow:
   *        [array]  args    Arguments given via param
   *        [string] key     Args key to search for
   *        [string] label   Prompt for STDOUT
   *        [array]  choices Menu options for the user, may be a collection
   * @return int
   */
  public static function day(array $arg_options = array()) {
    $default_options = array(
      'args' => array(),
      'key' => 'day',
      'label' => 'Select a day',
      'choices' => array(
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
      ),
    );
    $options         = array_merge($default_options, $arg_options);
    if (isset($options['args'][$options['key']])) {
      $day        = date('l', strtotime($options['args'][$options['key']]));
      $day_number = array_search($day, $options['choices']);
    } else {
      $day_number = self::menu(
        array(
          'choices' => $options['choices'],
          'default' => 'Sunday',
          'message' => $options['label'],
        )
      );
    }
    return $day_number;
  }

  /**
   * Produces a menu with the given attributes
   *
   * @param array $arg_options Elements as follow:
   *        [array]  args    Arguments given via param
   *        [string] key     Args key to search for
   *        [string] label   Prompt for STDOUT
   *        [array]  choices Menu options for the user, may be a collection
   *        [Site]   site    Site object to gather environment choices from
   * @return string Either the selection, its index, or the default
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
      array(
        'choices'      => $choices,
        'default'      => 'dev',
        'message'      => $options['label'],
        'return_value' => true
      )
    );
    return $menu;
  }

  /**
   * Produces a menu with the given attributes
   *
   * @param array $arg_options Elements as follow:
   *        array  choices      Menu options for the user
   *        mixed  default      Given as null option in the menu
   *        string message      Prompt printed to STDOUT
   *        bool   return_value If true, returns selection. False, the index
   * @return string Either the selection, its index, or the default
   */
  public static function menu(array $arg_options = array()) {
    $default_options = array(
      'choices'      => array(self::$NULL_INPUTS[0]),
      'default'      => null,
      'message'      => 'Select one',
      'return_value' => false
    );
    $options         = array_merge($default_options, $arg_options);

    if (count($options['choices']) == 1) {
      $index = 0;
    } else {
      $index = \cli\Streams::menu(
        $options['choices'],
        $options['default'],
        $options['message']
      );
    }
    if ($options['return_value']) {
      return $options['choices'][$index];
    }
    return $index;
  }

  /**
   * Returns $args[$key] if exists, $default otherwise
   *
   * @param array $arg_options Elements as follow:
   *        string key     Index of arg to return
   *        array  choices    Args to search for key
   *        mixed  default Returned if $args[$key] DNE
   * @return mixed Either $args[$key] or $default
   */
  public static function optional(array $arg_options = array()) {
    $default_options = array(
      'key'     => 0,
      'choices' => array(),
      'default' => null,
    );
    $options         = array_merge($default_options, $arg_options);

    if (isset($options['choices'][$options['key']])) {
      return $options['choices'][$options['key']];
    }
    return $options['default'];
  }

  /**
   * Input helper that provides interactive menu to select org name
   *
   * @param array $arg_options Elements as follow:
   *        array  args       The args passed in from argv
   *        string key        Args key to search for
   *        string default    Returned if arg and stdin fail in interactive
   *        array  allow_none True to permit no selection to be an option
   * @return string ID of selected organization
  */
  public static function orgId(array $arg_options = array()) {
    $default_options = array(
      'args'       => array(),
      'key'        => 'org',
      'default'    => null,
      'allow_none' => true,
    );
    $options         = array_merge($default_options, $arg_options);

    $arguments = $options['args'];
    $key       = $options['key'];
    if (!isset($arguments[$key]) && isset($_SERVER['TERMINUS_ORG'])) {
      $arguments[$key] = $_SERVER['TERMINUS_ORG'];
    }

    $org_list = self::orgList($options);
    $flip    = array_flip($org_list);
    if (isset($arguments[$key])) {
      if (isset($flip[$arguments[$key]])) {
        return $flip[$arguments[$key]];
      } elseif (isset($org_list[$arguments[$key]])) {
        return $arguments[$key];
      } elseif (in_array($arguments[$key], self::$NULL_INPUTS)
        || !empty($arguments)
      ) {
        return $options['default'];
      }
    }

    // include the Org ID in the output menu
    $org_list_with_id = array();
    foreach ($org_list as $id => $name) {
      if ($name == 'None') {
        $org_list_with_id[$id] = $name;
        continue;
      }
      $org_list_with_id[$id] = sprintf("%s (%s)", $name, $id);
    }

    $org = self::menu(
      array(
        'choices' => $org_list_with_id,
        'default' => false,
        'message' => 'Choose an organization',
      )
    );

    if ($org == '-') {
      return $options['default'];
    }
    return $org;
  }

  /**
   * Returns an array listing organizaitions applicable to user
   *
   * @param array $arg_options Elements as follow:
   *        bool allow_none True to allow the "none" option
   * @return array A list of organizations
  */
  public static function orgList(array $arg_options = array()) {
    $default_options = array('allow_none' => true);
    $options         = array_merge($default_options, $arg_options);

    $org_list = array();
    if ($options['allow_none']) {
      $org_list = array('-' => 'None');
    }
    $user          = new User();
    $organizations = $user->organizations->all();
    foreach ($organizations as $id => $org) {
      $org_data                  = $org->get('organization');
      $org_list[$org->get('id')] = $org_data->profile->name;
    }
    return $org_list;
  }

  /**
   * Input helper that provides interactive menu to select org name
   *
   * @param array $arg_options Elements as follow:
   *        array  args The args passed in from argv
   *        string key  Args key to search for
   * @return string Site name
  */
  public static function orgName(array $arg_options = array()) {
    $default_options = array(
      'args' => array(),
      'key'  => 'org',
    );
    $options         = array_merge($default_options, $arg_options);

    $org_list = self::orgList();
    if (isset($options['args'][$options['key']])) {
      //If org id is sent, fetch the name
      if (isset($org_list[$options['args'][$options['key']]])) {
        return $org_list[$options['args'][$options['key']]];
      }
      return $options['args'][$options['key']];
    }
    $org = self::menu(
      array(
        'choices' => $org_list,
        'default' => false,
        'message' => 'Choose an organization',
      )
    );
    return $org_list[$org];
  }

  /**
   * Prompt the user for input
   *
   * @param array $arg_options Elements as follow:
   *        string message Message to give at prompt
   *        mixed  default Returned if user does not select a valid option
   * @return string
   * @throws TerminusException
   */
  public static function prompt(array $arg_options = array()) {
    $default_options = array(
      'message' => '',
      'default' => null,
    );
    $options         = array_merge($default_options, $arg_options);

    try {
      $response = \cli\prompt($options['message']);
    } catch (\Exception $e) {
      throw new TerminusException($e->getMessage, array(), 1);
    }
    if (empty($response)) {
      return $options['default'];
    }
    return $response;
  }

  /**
   * Gets input from STDIN silently
   * By: Troels Knak-Nielsen
   * From: http://www.sitepoint.com/interactive-cli-password-prompt-in-php/
   *
   * @param array $arg_options Elements as follow:
   *        string message Message to give at prompt
   *        mixed  default Returned if user does not select a valid option
   * @return string
   * @throws TerminusException
   */
  public static function promptSecret(array $arg_options = array()) {
    $default_options = array(
      'message' => '',
      'default' => null,
    );
    $options         = array_merge($default_options, $arg_options);

    if (Utils\isWindows()) {
      $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
      file_put_contents(
        $vbscript, 'wscript.echo(InputBox("'
        . addslashes($options['message'])
        . '", "", "password here"))'
      );
      $command  = "cscript //nologo " . escapeshellarg($vbscript);
      $response = rtrim(shell_exec($command));
      unlink($vbscript);
    } else {
      $command = "/usr/bin/env bash -c 'echo OK'";
      if (rtrim(shell_exec($command)) !== 'OK') {
        throw new TerminusException("Can't invoke bash", array(), 1);
      }
      $command  = "/usr/bin/env bash -c 'read -s -p \""
        . addslashes($options['message'])
        . "\" mypassword && echo \$mypassword'";
      $response = rtrim(shell_exec($command));
      echo "\n";
    }
    if (empty($response)) {
      return $options['default'];
    }
    return $response;
  }

  /**
   * Helper function to get role
   *
   * @param array $arg_options Elements as follow:
   *        array  $assoc_args Argument array passed from commands
   *        string $message    Prompt to STDOUT
   * @return string Name of role
   */
  static public function role(array $arg_options = array()) {
    $default_options = array(
      'args'    => array(),
      'key'     => 'role',
      'message' => 'Select a role for this member',
    );
    $options         = array_merge($default_options, $arg_options);

    $roles = array('developer', 'team_member', 'admin');
    if (isset($options['args'][$options['key']])
      && in_array(strtolower($options['args'][$options['key']]), $roles)
    ) {
      return $options['args'][$options['key']];
    }
    $role = strtolower(
      $roles[self::menu(
        array(
          'choices' => $roles,
          'message' => $options['message'],
        )
      )]
    );
    return $role;
  }

  /**
   * Input helper that provides interactive site list
   *
   * @param array  $args  The args passed in from argv
   * @param string $key   Args key to search for
   * @param string $label Prompt for STDOUT
   * @return string Site name
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
      function(Site $site) {
        $site_name = $site->get('name');
        return $site_name;
      }, $sites
    );

    $choices = array();
    foreach ($sitenames as $sitename) {
      $choices[$sitename] = $sitename;
    }
    $menu = self::menu(array('choices' => $choices, 'message' => $label));
    return $menu;
  }

  /**
   * Returns $args[key] if exists, then STDIN, then $default
   *
   * @param array  $args    Args already input
   * @param string $key     Key for searched-for argument
   * @param string $label   Prompt printed to STDOUT
   * @param mixed  $default Returns if no other choice
   *
   * @return string Either $args[$key], $default, or string from prompt
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
    $string = self::prompt($label);
    if ($string == '') {
      return $default;
    }
    return $string;
  }

  /**
   * Helper function to select valid upstream
   *
   * @param array  $args Args to parse value from
   * @param string $key  Index to search for in args
   * @param bool   $exit If true, throw error when no value is found
   * @return Upstream
   * @throws TerminusException
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
        self::menu(
          array('choices' => $upstreams->getMemberList('id', 'longname'))
        )
      );
    }
    return $upstream;
  }

  /**
   * Helper function to select Site Workflow
   *
   * @param Workflow[] $workflows Array of workflows to list
   * @param array      $args      Args to parse value from
   * @param string     $key       Index to search for in args
   * @return Workflow
   * @throws TerminusException
   */
  public static function workflow($workflows, $args = array(), $key = 'workflow_id') {
    if (isset($args['workflow_id'])) {
      $workflow_id = $args[$key];
    } else {
      $workflow_menu_args = array();

      foreach ($workflows as $workflow) {
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
      $workflow_id = self::menu(
        array(
          'choices' => $workflow_menu_args,
          'message' => 'Choose workflow'
        )
      );
    }

    $filtered_workflow = array_filter(
      $workflows,
      function($workflow) use ($workflow_id) {
        return $workflow->id == $workflow_id;
      }
    );

    if (count($filtered_workflow) > 0) {
      $workflow = array_values($filtered_workflow)[0];
      return $workflow;
    } else {
      throw new TerminusException(
        'Could not find workflow "{id}"',
        array('id' => $id),
        1
      );
    }
  }

  /**
   * Same as confirm but doesn't exit
   *
   * @param string $question Question to ask
   * @param array  $params   Args for vsprintf()
   *
   * @return bool
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
