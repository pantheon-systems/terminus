<?php

use Terminus\Auth;
use Terminus\Utils;
use Terminus\Helpers\Input;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\User;
use Terminus\Models\Collections\Sites;

/**
* Actions to be taken on an individual site
*/
class WorkflowsCommand extends TerminusCommand {

protected $_headers = false;

public function __construct() {
  Auth::ensureLogin();
  parent::__construct();
  $this->sites = new Sites();
}

  /**
   * List Worflows for a Site
   *
   * ## OPTIONS
   * [--site=<site>]
   * : Site from which to list workflows
   *
   * @subcommand list
   */
  public function index($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $workflows = $site->workflows->all();
    $data = array();
    foreach($workflows as $workflow) {
      $user = 'Pantheon';
      if (isset($workflow->get('user')->email)) {
        $user = $workflow->get('user')->email;
      }
      if ($workflow->get('total_time')) {
        $elapsed_time = $workflow->get('total_time');
      } else {
        $elapsed_time = time() - $workflow->get('created_at');
      }

      $data[] = array(
        'id'             => $workflow->id,
        'env'            => $workflow->get('environment'),
        'workflow'       => $workflow->get('description'),
        'user'           => $user,
        'status'         => $workflow->get('phase'),
        'time'           => sprintf("%ds", $elapsed_time)
      );
    }
    if (count($data) == 0) {
      $this->log()->warning('No workflows have been run on {site}', array('site' => $site->get('name')));
    }
    $this->output()->outputRecordList($data, array('update' => 'Last update'));
  }
}

\Terminus::addCommand('workflows', 'WorkflowsCommand');
