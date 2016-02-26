<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Workflow;

class Workflows extends TerminusCollection {
  /**
   * @var string
   */
  protected $environment;
  /**
   * @var object
   * @todo Find specific type for this
   */
  protected $owner;

  /**
   * Creates a new workflow and adds its data to the collection
   *
   * @param string $type    Type of workflow to create
   * @param array  $options Additional information for the request, with the
   *   following possible keys:
   *   - environment: string
   *   - params: associative array of parameters for the request
   * @return Workflow $model
   */
  public function create($type, array $options = array()) {
    $options = array_merge(array('params' => array()), $options);
    if (isset($options['environment'])) {
      $this->environment = $options['environment'];
    }
    $params = array_merge($this->getFetchArgs(), $options['params']);

    $results = $this->request->request(
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
   * @return string URL to use in fetch query
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
   * @param array $options Additional information for the request
   * @return void
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
   * Returns all existing workflows that have finished
   *
   * @return Workflow[]
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
   * @return Workflow[]
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
   * Get timestamp of most recently finished workflow
   *
   * @return int|null Timestamp
   */
  public function lastFinishedAt() {
    $workflows = $this->all();
    usort(
      $workflows,
      function($a, $b) {
        $a_finished_after_b = $a->get('finished_at') >= $b->get('finished_at');
        if ($a_finished_after_b) {
          $cmp = -1;
        } else {
          $cmp = 1;
        }
        return $cmp;
      }
    );
    if (count($workflows) > 0) {
      $timestamp = $workflows[0]->get('finished_at');
    } else {
      $timestamp = null;
    }
    return $timestamp;
  }

  /**
   * Get timestamp of most recently created Workflow
   *
   * @return int|null Timestamp
   */
  public function lastCreatedAt() {
    $workflows = $this->all();
    usort(
      $workflows,
      function($a, $b) {
        $a_created_after_b = $a->get('created_at') >= $b->get('created_at');
        if ($a_created_after_b) {
          $cmp = -1;
        } else {
          $cmp = 1;
        }
        return $cmp;
      }
    );
    if (count($workflows) > 0) {
      $timestamp = $workflows[0]->get('created_at');
    } else {
      $timestamp = null;
    }
    return $timestamp;
  }

  /**
   * Get most-recent workflow from existing collection that has logs
   *
   * @return Workflow|null
   */
  public function findLatestWithLogs() {
    $workflows = $this->allWithLogs();
    usort(
      $workflows,
      function($a, $b) {
        $a_finished_after_b = $a->get('finished_at') >= $b->get('finished_at');
        if ($a_finished_after_b) {
          $cmp = -1;
        } else {
          $cmp = 1;
        }
        return $cmp;
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
   * @return string
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
   * @param object $model_data Data to feed into attributes of new model
   * @param array  $options    Data to make properties of the new model
   * @return Workflow  The newly-added model
   */
  public function add($model_data, array $options = array()) {
    $model = parent::add($model_data, $options);
    $model->owner = $this->owner;
    return $model;
  }

}
