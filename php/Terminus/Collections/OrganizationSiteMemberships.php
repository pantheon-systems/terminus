<?php

namespace Terminus\Collections;
use Terminus\Request;
use \TerminusCommand;
use \Terminus\Models\OrganizationSiteMembership;

class OrganizationSiteMemberships {
  private $organization;
  private $models = array();

  public function __construct($options = array()) {
    $this->organization = $options['organization'];

    return $this;
  }

  public function fetch() {
    $response = TerminusCommand::paged_request(sprintf("organizations/%s/memberships/sites", $this->organization->id));

    foreach ($response['data'] as $membership_data) {
      $this->models[$membership_data->id] = new OrganizationSiteMembership($membership_data);
    }

    return $this;
  }

  public function get($id) {
    $model = array_key_exists($id, $this->models) ? $this->models[$id] : null;
    return $model;
  }

  public function all() {
    $models = array_values($this->models);
    return $models;
  }
}
