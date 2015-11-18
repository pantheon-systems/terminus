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
      $workflow_data = $workflow->serialize();
      unset($workflow_data['operations']);
      $data[] = $workflow_data;
    }
    if (count($data) == 0) {
      $this->log()->warning('No workflows have been run on {site}', array('site' => $site->get('name')));
    }
    $this->output()->outputRecordList($data, array('update' => 'Last update'));
  }

  /**
   * Show operation details for a workflow
   *
   * ## OPTIONS
   * [--workflow_id]
   * : Uuid of workflow to show
   * [--site=<site>]
   * : Site from which to list workflows
   *
   * @subcommand show
   */
  public function show($args, $assoc_args) {
    $site = $this->sites->get(Input::sitename($assoc_args));
    $workflow = Input::workflow($site, $assoc_args, 'workflow_id');

    $workflow_data = $workflow->serialize();
    if (Terminus::getConfig('format') == 'normal') {
      $operations_data = $workflow_data['operations'];
      unset($workflow_data['operations']);

      $this->output()->outputRecord($workflow_data);

      if (count($operations_data)) {
        $this->log()->info('Workflow operations:');
        $this->output()->outputRecordList($operations_data);
      } else {
        $this->log()->info('Workflow has no operations');
      }
    } else {
      $this->output()->outputRecordList($workflow_data);
    }
  }
}

\Terminus::addCommand('workflows', 'WorkflowsCommand');
