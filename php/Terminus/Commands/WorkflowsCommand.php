<?php

namespace Terminus\Commands;

use Terminus\Utils;
use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\User;
use Terminus\Models\Collections\Sites;

define("WORKFLOWS_WATCH_INTERVAL", 5);

/**
* Actions to be taken on an individual site
*
* @command workflows
*/
class WorkflowsCommand extends TerminusCommand {
  protected $_headers = false;

  /**
   * Object constructor.
   *
   * @param array $options Options to construct the command object
   * @return WorkflowsCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * List Workflows for a Site
   *
   * ## OPTIONS
   * [--site=<site>]
   * : Site from which to list workflows
   *
   * @subcommand list
   */
  public function index($args, $assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(['args' => $assoc_args])
    );
    $site->workflows->fetch(['paged' => false]);
    $workflows = $site->workflows->all();

    $data = [];
    foreach ($workflows as $workflow) {
      $workflow_data = $workflow->serialize();
      unset($workflow_data['operations']);
      $data[] = $workflow_data;
    }
    if (count($data) == 0) {
      $this->log()->warning(
        'No workflows have been run on {site}.',
        ['site' => $site->get('name')]
      );
    }
    $this->output()->outputRecordList($data);
  }

  /**
   * Show operation details for a workflow
   *
   * ## OPTIONS
   * [--workflow-id=<workflow-id>]
   * : UUID of workflow to show
   * [--site=<site>]
   * : Site from which to list workflows
   * [--latest-with-logs]
   * : Display the most-recent workflow with logs
   *
   * @subcommand show
   */
  public function show($args, $assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(['args' => $assoc_args])
    );

    if (isset($assoc_args['workflow-id'])) {
      $workflow_id = $assoc_args['workflow-id'];
      $model_data  = (object)['id' => $workflow_id];
      $workflow    = $site->workflows->add($model_data);
    } elseif (isset($assoc_args['latest-with-logs'])) {
      $site->workflows->fetch(['paged' => false]);
      $workflow = $site->workflows->findLatestWithLogs();
      if (!$workflow) {
        $this->log()->info('No recent workflow has logs');
        return;
      }
    } else {
      $site->workflows->fetch(['paged' => false]);
      $workflows = $site->workflows->all();
      $workflow  = $this->input()->workflow(compact('workflows'));
    }
    $workflow->fetchWithLogs();

    $workflow_data = $workflow->serialize();
    if ($this->log()->getOptions('logFormat') == 'normal') {
      unset($workflow_data['operations']);
      $this->output()->outputRecord($workflow_data);

      $operations = $workflow->operations();
      if (count($operations)) {
        // First output a table of operations without logs
        $operations_data = array_map(
          function($operation) {
            $operation_data = $operation->serialize();
            unset($operation_data['id']);
            unset($operation_data['log_output']);
            return $operation_data;
          },
          $operations
        );

        $this->output()->outputRecordList(
          $operations_data,
          ['description' => 'Operation Description']
        );

        // Second output the logs
        foreach ($operations as $operation) {
          if ($operation->has('log_output')) {
            $log_msg = sprintf(
              "\n------ %s ------\n%s",
              $operation->description(),
              $operation->get('log_output')
            );
            $this->output()->outputValue($log_msg);
          }
        }
      } else {
        $this->output()->outputValue('Workflow has no operations');
      }
    } else {
      $this->output()->outputRecord($workflow_data);
    }
  }

  /**
   * Streams new and finished workflows to the console
   *
   * ## OPTIONS
   * [--site=<site>]
   * : Site from which to list workflows
   *
   * @subcommand watch
   */
  public function watch($args, $assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(['args' => $assoc_args])
    );

    // Keep track of workflows that have been printed.
    // This is necessary because the local clock may drift from
    // the server's clock, causing events to be printed twice.
    $started  = [];
    $finished = [];

    $this->log()->info('Watching workflows...');
    $site->workflows->fetchWithOperations();
    while (true) {
      $last_created_at  = $site->workflows->lastCreatedAt();
      $last_finished_at = $site->workflows->lastFinishedAt();
      sleep(WORKFLOWS_WATCH_INTERVAL);
      $site->workflows->fetchWithOperations();

      $workflows = $site->workflows->all();
      foreach ($workflows as $workflow) {
        if (($workflow->get('created_at') > $last_created_at)
          && !in_array($workflow->get('id'), $started)
        ) {
          array_push($started, $workflow->get('id'));

          $started_message = 'Started {id} {description} ({env}) at {time}';
          $started_context = [
            'id'          => $workflow->get('id'),
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date(
              TERMINUS_DATE_FORMAT,
              $workflow->get('started_at')
            ),
          ];
          $this->log()->info($started_message, $started_context);
        }

        if (($workflow->get('finished_at') > $last_finished_at)
          && !in_array($workflow->get('id'), $finished)
        ) {
          array_push($finished, $workflow->get('id'));

          $finished_message
            = 'Finished workflow {id} {description} ({env}) at {time}';
          $finished_context = [
            'id'          => $workflow->get('id'),
            'description' => $workflow->get('description'),
            'env'         => $workflow->get('environment'),
            'time'        => date(
              TERMINUS_DATE_FORMAT,
              $workflow->get('finished_at')
            ),
          ];
          $this->log()->info($finished_message, $finished_context);

          if ($workflow->get('has_operation_log_output')) {
            $workflow->fetchWithLogs();
            $operations = $workflow->operations();
            foreach ($operations as $operation) {
              if ($operation->has('log_output')) {
                $log_msg = sprintf(
                  "\n------ %s (%s) ------\n%s",
                  $operation->description(),
                  $operation->get('environment'),
                  $operation->get('log_output')
                );
                $this->log()->info($log_msg);
              }
            }
          }
        }
      }
    }
  }

}
