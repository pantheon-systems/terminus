<?php

namespace Terminus\Models\Collections;

use TerminusCommand;
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

    $results = TerminusCommand::simpleRequest(
      $this->getFetchUrl(),
      array(
        'method'   => 'post',
        'data'     => array(
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
   * Fetches model data from API and instantiates its model instances
   *
   * @param [boolean] $paged True to use paginated API requests
   * @return [Workflows] $this
   */
  public function fetch($paged = false) {
    parent::fetch(true);
    return $this;
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
          $this->owner->organization->id
        );
          break;
    }
    return $url;
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

}
