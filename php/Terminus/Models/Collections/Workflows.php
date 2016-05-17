<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Workflow;
use Terminus\Session;

class Workflows extends NewCollection {
  /**
   * @var Environment
   */
  public $environment;
  /**
   * @var Organization
   */
  public $organization;
  /**
   * @var string
   */
  public $owner;
  /**
   * @var Site
   */
  public $site;
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Workflow';
  
  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Workflows
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    if (isset($options['site'])) {
      $this->site  = $options['site'];
      $this->url   = "sites/{$this->site->id}/workflows";
      $this->owner = 'site';
    } elseif (isset($options['environment'])) {
      $this->environment = $options['environment'];
      $this->url         = sprintf(
        'sites/%s/environments/%s/workflows',
        $this->environment->site->id,
        $this->environment->id
      );
      $this->owner       = 'environment';
    } elseif (isset($options['user'])) {
      $this->user  = $options['user'];
      $this->url   = "users/{$this->user->id}/workflows";
      $this->owner = 'user';
    } elseif (isset($options['organization'])) {
      $this->organization = $options['organization'];
      $this->url          = sprintf(
        'users/%s/organizations/%s/workflows',
        Session::getUser()->id,
        $this->organization->id
      );
      $this->owner        = 'organization'; 
    }
  }

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
  public function create($type, array $options = []) {
    $results = $this->request->request(
      $this->url,
      [
        'method'      => 'post',
        'form_params' => [
          'type'   => $type,
          'params' => (object)$options['params'],
        ],
      ]
    );

    $model = new Workflow($results['data'], ['collection' => $this,]);
    $this->add($model);
    return $model;
  }

  /**
   * Fetches workflow data hydrated with operations
   *
   * @param array $options Additional information for the request
   * @return void
   */
  public function fetchWithOperations($options = []) {
    $options = array_merge(
      $options,
      ['params' => ['query' => ['hydrate' => 'operations',],],]
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

}
