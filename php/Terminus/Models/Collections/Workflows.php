<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Workflow;
use Terminus\Models\Collections\TerminusCollection;

class Workflows extends TerminusCollection {
  protected $environment;
  protected $owner;

  /**
   * Creates a new workflow and adds its data to the collection
   *
   * @param [string] $type    Type of workflow to create
   * @param [array]  $options Additional information for the request
   *        [string] environment UUID of environment running workflow
   *        [array]  params      Parameters for the request
   * @return [TerminusModel] $model
   */
  public function create($type, $options = array()) {
    $options = array_merge(array('params' => array()), $options);
    if (isset($options['environment'])) {
      $this->environment = $options['environment'];
    }
    $params = array_merge($this->getFetchArgs(), $options['params']);

    $results = $this->request->simpleRequest(
      $this->getFetchUrl(),
      array(
        'method'      => 'post',
        'form_params' => array(
          'type'   => $type,
          'params' => (object)$params
        )
      )
    );

    $model = new Workflow(
      $results['data'],
      array(
        'owner' => $this->owner,
      )
    );
    $this->add($model);
    return $model;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = '';
    switch ($this->getOwnerName()) {
      case 'user':
        $url = sprintf(
          'users/%s/workflows',
          $this->owner->id
        );
          break;
      case 'site':
        $replacement = $this->owner->get('id');
        if (isset($this->environment)) {
          $replacement = sprintf(
            '%s/environments/%s',
            $this->owner->get('id'),
            $this->environment
          );
        }
        $url = sprintf(
          'sites/%s/workflows',
          $replacement
        );
          break;
      case 'organization':
        $url = sprintf(
          'users/%s/organizations/%s/workflows',
          $this->owner->user->id,
          $this->owner->get('id')
        );
          break;
    }
    return $url;
  }

  /**
   * Fetches workflow data hydrated with operations
   *
   * @param [array] $options Additional information for the request
   * @return [Workflows] $this
   */
  public function fetchWithOperations($options = array()) {
    $options = array_merge(
      $options,
      array(
        'fetch_args' => array(
          'query' => array(
            'hydrate' => 'operations'
          )
        )
      )
    );
    $this->fetch($options);
  }

  /**
   * Fetches workflow data hydrated with operations and logs
   *
   * @param [array] $options Additional information for the request
   * @return [Workflows] $this
   */
  public function fetchWithOperationsAndLogs($options = array()) {
    $options = array_merge(
      $options,
      array(
        'fetch_args' => array(
          'query' => array(
            'hydrate' => 'operations_with_logs'
          )
        )
      )
    );
    $this->fetch($options);
  }

  /**
   * Returns all existing workflows that have finished
   *
   * @return [Array<Workflows>] $workflows
   */
  public function allFinished() {
    $workflows = array_filter(
      $this->all(),
      function($workflow) {
        $is_finished = $workflow->isFinished();
        return $is_finished;
      }
    );
    return $workflows;
  }

  /**
   * Returns all existing workflows that contain logs
   *
   * @return [Array<Workflows>] $workflows
   */
  public function allWithLogs() {
    $workflows = $this->allFinished();
    $workflows = array_filter(
      $workflows,
      function($workflow) {
        $has_logs = $workflow->get('has_operation_log_output');
        return $has_logs;
      }
    );

    return $workflows;
  }

  /**
   * Get most-recent workflow from existingcollection that has logs
   *
   * @return [Workflow] $workflow
   */
  public function findLatestWithLogs() {
    $workflows = $this->allWithLogs();
    usort(
      $workflows,
      function($a, $b) {
        $a_finished_after_b = $a->get('finished_at') >= $b->get('finished_at');
        if ($a_finished_after_b) {
          $cmp = -1;
          return $cmp;
        } else {
          $cmp = 1;
          return $cmp;
        }
      }
    );

    if (count($workflows) > 0) {
      $workflow = $workflows[0];
    } else {
      $workflow = null;
    }
    return $workflow;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return [string] $this->owner_type or $owner_name
   */
  protected function getOwnerName() {
    if (isset($this->owner_type)) {
      return $this->owner_type;
    }
    $owner_name = strtolower(
      str_replace(
        array('Terminus\\', 'Models\\', 'Collections\\'),
        '',
        get_class($this->owner)
      )
    );
    return $owner_name;
  }

  /**
   * Adds a model to this collection
   *
   * @param [stdClass] $model_data Data to feed into attributes of new model
   * @param [array]    $options    Data to make properties of the new model
   * @return [mixed] $model newly added model
   */
  public function add($model_data, $options = array()) {
    $model = parent::add($model_data, $options = array());
    $model->owner = $this->owner;
    return $model;
  }

}
