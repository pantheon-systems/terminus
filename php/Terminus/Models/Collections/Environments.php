<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Environment;
use Terminus\Models\Workflow;

class Environments extends TerminusCollection {

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
      array(
        'params' => array(
          'environment_id' => $to_env_id,
          'deploy'         => array(
            'clone_database' => array('from_environment' => $from_env->id),
            'clone_files'    => array('from_environment' => $from_env->id),
            'annotation'     => sprintf(
              'Create the "%s" environment.',
              $to_env_id
            )
          )
        )
      )
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

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = 'sites/' . $this->site->get('id') . '/environments';
    return $url;
  }

  /**
   * Names the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'site';
    return $owner_name;
  }

}
