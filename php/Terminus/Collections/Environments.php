<?php

namespace Terminus\Collections;

use Terminus\Models\Environment;

class Environments extends TerminusCollection {
  /**
   * @var Site
   */
  public $site;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Environment';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url = "sites/{$this->site->id}/environments";
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
            ),
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
    $ids = array_keys($this->getMembers());

    //Reorder environments to put dev/test/live first
    $default_ids  = array('dev', 'test', 'live');
    $multidev_ids = array_diff($ids, $default_ids);
    $ids          = array_merge($default_ids, $multidev_ids);

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
