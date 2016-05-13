<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Environment;

class Environments extends NewCollection {
  /**
   * @var Site
   */
  public $site;

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Environments
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url  = "sites/{$this->site->id}/environments";
  }

  /**
   * Creates a multidev environment
   *
   * @param string      $to_env_id Name of new the environment
   * @param Environment $from_env  Environment to clone from
   * @return Workflow
   */
  public function create($to_env_id, Environment $from_env) {
    $workflow = $this->site->workflows->create(
      'create_cloud_development_environment',
      [
        'params' => [
          'environment_id' => $to_env_id,
          'deploy'         => [
            'clone_database' => ['from_environment' => $from_env->id,],
            'clone_files'    => ['from_environment' => $from_env->id,],
            'annotation'     => sprintf(
              'Create the "%s" environment.',
              $to_env_id
            )
          ],
        ],
      ]
    );
    return $workflow;
  }

  /**
   * List Environment IDs, with Dev/Test/Live first
   *
   * @return string[] $ids
   */
  public function ids() {
    //Reorder environments to put dev/test/live first
    $default_ids  = ['dev', 'test', 'live',];
    $ids          = array_merge(
      ['dev', 'test', 'live',],
      array_diff(array_keys($this->models), $default_ids)
    );

    return $ids;
  }

  /**
   * Returns a list of all multidev environments on the collection-owning Site
   *
   * @return Environment[]
   */
  public function multidev() {
    $environments = array_filter(
      $this->getMembers(),
      function($environment) {
        $is_multidev = $environment->isMultidev();
        return $is_multidev;
      }
    );
    return $environments;
  }

}
