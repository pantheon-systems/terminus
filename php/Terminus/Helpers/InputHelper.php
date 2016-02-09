<?php

namespace Terminus\Helpers;

use Terminus\Exceptions\TerminusException;
use Terminus\Helpers\TerminusHelper;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Collections\Upstreams;
use Terminus\Models\Site;
use Terminus\Models\Upstream;
use Terminus\Models\User;
use Terminus\Models\Workflow;
use Terminus\Session;
use Terminus\Utils;

/**
 * Helper class to handle inputs
 */
class InputHelper extends TerminusHelper {
  private $NULL_INPUTS = ['', 'false', 'None', 'Null', '0'];

  /**
   * Produces a menu to select a backup
   *
   * @param array $arg_options Elements as follow:
   *        [string] label   Prompt for STDOUT
   *        [array]  backups Array of Backup objects
   * @return \stdClass An object representing the backup desired
   * @throws TerminusException
   */
  public function backup(array $arg_options = []) {
    $default_options = [
      'label'   => 'Select a backup',
      'backups' => [],
    ];
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

    $choices = [];
    foreach ($backups as $folder => $backup) {
      if ($backup->get('filename') == null) {
        unset($backups[$folder]);
        continue;
      }
      $choices[] = $backup->get('filename');
    }
    $choice        = $this->menu(
      ['choices' => $choices, 'message' => $options['label']]
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
   * @throws TerminusException
   */
  public function backupElement(array $arg_options = []) {
    $default_options = [
      'args'    => [],
      'key'     => 'element',
      'label'   => 'Select backup element',
      'choices' => ['code', 'database', 'files'],
    ];
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
        ['element' => $args[$key], 'choices' => implode(', ', $choices)],
        1
      );
    }

    $element = $this->menu(
      [
        'choices' => $choices,
        'message' => $options['label'],
        'return_value' => true,
      ]
    );
    return $element;
  }

  /**
   * Asks for confirmation before running a destructive operation.
   *
   * @param array $arg_options Elements as follow:
   *        string question Prompt text
   *        array  params   Elements to interpolate into the prompt text
   *        array  args     Arguments given via param
   *        string key      Args key to search for
   *        bool   exit     If true, exit when turned down
   * @return bool True if prompt is accepted
   */
  function confirm(array $arg_options = []) {
    $default_options = [
      'message' => 'Do you want to continue?',
      'context' => [],
      'exit'    => true,
      'args'    => [],
      'key'     => 'force'
    ];
    $options         = array_merge($default_options, $arg_options);
    if ($this->command->runner->getConfig('yes')
      || (
        isset($options['key'])
        && isset($options['args'][$options['key']])
        && (boolean)$options['args'][$options['key']]
      )
    ) {
      return true;
    }
    $question = vsprintf($options['message'], $options['context']);
    $this->command->output()->line($question . ' [y/n]');
    $answer = trim(fgets(STDIN));

    if ($answer != 'y') {
      if ($options['exit']) {
        exit((integer)$options['exit']);
      }
      return false;
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
  public function day(array $arg_options = []) {
    $default_options = [
      'args' => [],
      'key' => 'day',
      'label' => 'Select a day',
      'choices' => [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
      ],
    ];
    $options         = array_merge($default_options, $arg_options);
    if (isset($options['args'][$options['key']])) {
      $day        = date('l', strtotime($options['args'][$options['key']]));
      $day_number = array_search($day, $options['choices']);
    } else {
      $day_number = $this->menu(
        [
          'choices' => $options['choices'],
          'default' => 'Sunday',
          'message' => $options['label'],
        ]
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
  public function env(array $arg_options = []) {
    $default_options = [
      'args'    => [],
      'key'     => 'env',
      'label'   => 'Choose environment',
      'choices' => ['dev', 'test', 'live'],
      'site'    => null,
    ];
    $options         = array_merge($default_options, $arg_options);
    if (isset($options['args'][$options['key']])) {
      return $options['args'][$options['key']];
    }
    if (in_array($options['key'], ['env', 'from-env'])) {
      if (isset($_SERVER['TERMINUS_ENV'])) {
        return $_SERVER['TERMINUS_ENV'];
      }
    }
    $choices = $options['choices'];
    if (get_class($options['site']) == 'Site') {
      $choices = $options['site']->environments->ids();
    }

    $menu = $this->menu(
      [
        'choices'      => $choices,
        'default'      => 'dev',
        'message'      => $options['label'],
        'return_value' => true
      ]
    );
    return $menu;
  }

  /**
   * Returns the null input list
   *
   * @return string[]
   */
  public function getNullInputs() {
    return $this->NULL_INPUTS;
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
  public function menu(array $arg_options = []) {
    $default_options = [
      'choices'      => [$this->NULL_INPUTS[0]],
      'default'      => null,
      'message'      => 'Select one',
      'return_value' => false
    ];
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
  public function optional(array $arg_options = []) {
    $default_options = [
      'key'     => 0,
      'choices' => [],
      'default' => null,
    ];
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
   * @throws TerminusException
  */
  public function orgId(array $arg_options = []) {
    $default_options = [
      'args'       => [],
      'key'        => 'org',
      'default'    => null,
      'allow_none' => true,
    ];
    $options         = array_merge($default_options, $arg_options);

    $arguments = $options['args'];
    $key       = $options['key'];
    $org_list  = $this->orgList($options);
    if (isset($arguments[$key])) {
      if ($id = array_search($arguments[$key], $org_list)) {
        return $id;
      }
      return $arguments[$key];
    } else if (isset($_SERVER['TERMINUS_ORG'])) {
      return $_SERVER['TERMINUS_ORG'];
    }
    if (count($org_list) == 0) {
      if ($options['allow_none']) {
        return $options['default'];
      }
      throw new TerminusException('You are not a member of an organization.');
    }
    if (count($org_list) == 1) {
      $org_ids = array_keys($org_list);
      $org     = array_shift($org_ids);
      return $org;
    }
    // Include the Org ID in the output menu
    $org_list_with_ids = [];
    if ($options['allow_none']) {
      $org_list_with_ids['-'] = 'None';
    }
    foreach ($org_list as $id => $name) {
      $org_list_with_id[$id] = sprintf("%s (%s)", $name, $id);
    }

    $org = $this->menu(
      [
        'choices' => $org_list_with_id,
        'default' => false,
        'message' => 'Choose an organization',
      ]
    );

    if ($org == '-') {
      return $options['default'];
    }
    return $org;
  }

  /**
   * Input helper that provides interactive menu to select org name
   *
   * @param array $arg_options Elements as follow:
   *        array  args The args passed in from argv
   *        string key  Args key to search for
   * @return string Site name
  */
  public function orgName(array $arg_options = []) {
    $default_options = [
      'args' => [],
      'key'  => 'org',
    ];
    $options         = array_merge($default_options, $arg_options);

    $org_list = $this->orgList();
    if (isset($options['args'][$options['key']])) {
      //If org id is sent, fetch the name
      if (isset($org_list[$options['args'][$options['key']]])) {
        return $org_list[$options['args'][$options['key']]];
      }
      return $options['args'][$options['key']];
    }
    $org = $this->menu(
      [
        'choices' => $org_list,
        'default' => false,
        'message' => 'Choose an organization',
      ]
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
  public function prompt(array $arg_options = []) {
    $default_options = [
      'message' => '',
      'default' => null,
    ];
    $options         = array_merge($default_options, $arg_options);

    try {
      $response = \cli\prompt($options['message']);
    } catch (\Exception $e) {
      throw new TerminusException($e->getMessage, [], 1);
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
  public function promptSecret(array $arg_options = []) {
    $default_options = [
      'message' => '',
      'default' => null,
    ];
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
        throw new TerminusException("Can't invoke bash", [], 1);
      }
      $command  = "/usr/bin/env bash -c 'read -s -p \""
        . addslashes($options['message'])
        . "\" mypassword && echo \$mypassword'";
      $response = rtrim(shell_exec($command));
      $this->command->output()->line();
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
   *        array  assoc_args Argument array passed from commands
   *        string message    Prompt to STDOUT
   * @return string Name of role
   */
  public function role(array $arg_options = []) {
    $default_options = [
      'args'    => [],
      'key'     => 'role',
      'message' => 'Select a role for this member',
    ];
    $options         = array_merge($default_options, $arg_options);

    $roles = ['developer', 'team_member', 'admin'];
    if (isset($options['args'][$options['key']])
      && in_array(strtolower($options['args'][$options['key']]), $roles)
    ) {
      return $options['args'][$options['key']];
    }
    $role = strtolower(
      $roles[$this->menu(
        [
          'choices' => $roles,
          'message' => $options['message'],
        ]
      )]
    );
    return $role;
  }

  /**
   * Input helper that provides interactive site list
   *
   * @param array $arg_options Elements as follow:
   *        array  args    The args passed in from argv
   *        string key     Args key to search for
   *        string message Prompt for STDOUT
   * @return string Site name
  */
  public function siteName(array $arg_options = []) {
    $default_options = [
      'args'  => [],
      'key'   => 'site',
      'message' => 'Choose site',
    ];
    $options         = array_merge($default_options, $arg_options);

    // return early if sitename is provided in args
    if (isset($options['args'][$options['key']])) {
      return $options['args'][$options['key']];
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

    $choices = [];
    foreach ($sitenames as $sitename) {
      $choices[$sitename] = $sitename;
    }
    $menu = $this->menu(
      ['choices' => $choices, 'message' => $options['message']]
    );
    return $menu;
  }

  /**
   * Returns $args[key] if exists, then STDIN, then $default
   *
   * @param array $arg_options Elements as follow:
   *        array  args    Args already input
   *        string key     Key for searched-for argument
   *        string message Prompt printed to STDOUT
   *        mixed  default Returns if no other choice
   * @return string Either $args[$key], $default, or string from prompt
   */
  public function string(array $arg_options = []) {
    $default_options = [
      'args'    => [],
      'key'     => 0,
      'message' => 'Enter',
      'default' => null,
    ];
    $options         = array_merge($default_options, $arg_options);

    if (isset($options['args'][$options['key']])) {
      return $options['args'][$options['key']];
    }
    if ($this->command->log()->options['logFormat'] != 'normal') {
      return $options['default'];
    }
    $string = $this->prompt($options);
    return $string;
  }

  /**
   * Helper function to select valid upstream
   *
   * @param array $arg_options Elements as follow:
   *        array  args Args to parse value from
   *        string key  Index to search for in args
   *        bool   exit If true, throw error when no value is found
   * @return Upstream
   * @throws TerminusException
   */
  public function upstream(array $arg_options = []) {
    $default_options = [
      'args' => [],
      'key'  => 'upstream',
      'exit' => true
    ];
    $options         = array_merge($default_options, $arg_options);

    $upstreams = new Upstreams();
    if (isset($options['args'][$options['key']])) {
      $upstream = $upstreams->getByIdOrName($options['args'][$options['key']]);
      if ($upstream == null) {
        throw new TerminusException(
          'Could not find upstream: {upstream}',
          ['upstream' => $options['args'][$options['key']]],
          (integer)$options['exit']
        );
      }
    } else {
      $upstream = $upstreams->get(
        $this->menu(
          ['choices' => $upstreams->getMemberList('id', 'longname')]
        )
      );
    }
    return $upstream;
  }

  /**
   * Helper function to select Site Workflow
   *
   * @param array $arg_options Elements as follow:
   *        Workflow[] workflows Array of workflows to list
   *        array      args      Args to parse value from
   *        string     key       Index to search for in args
   * @return Workflow
   * @throws TerminusException
   */
  public function workflow(array $arg_options = []) {
    $default_options = [
      'workflows' => [],
      'args'      => [],
      'key'       => 'workflow_id'
    ];
    $options         = array_merge($default_options, $arg_options);

    if (isset($options['args'][$options['key']])) {
      $workflow_id = $options['args'][$options['key']];
    } else {
      $workflow_menu_args = [];

      foreach ($options['workflows'] as $workflow) {
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
      $workflow_id = $this->menu(
        [
          'choices' => $workflow_menu_args,
          'message' => 'Choose workflow'
        ]
      );
    }

    $filtered_workflow = array_filter(
      $options['workflows'],
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
        compact('id'),
        1
      );
    }
  }

  /**
   * Returns an array listing organizaitions applicable to user
   *
   * @param array $arg_options Elements as follow:
   *        bool allow_none True to allow the "none" option
   * @return array A list of organizations
  */
  private function orgList(array $arg_options = []) {
    $default_options = ['allow_none' => true];
    $options         = array_merge($default_options, $arg_options);

    $org_list = [];
    if ($options['allow_none']) {
      $org_list = ['-' => 'None'];
    }
    $user          = Session::getUser();
    $organizations = $user->organizations->all();
    foreach ($organizations as $id => $org) {
      $org_data                  = $org->get('organization');
      $org_list[$org->get('id')] = $org_data->profile->name;
    }
    return $org_list;
  }

}
