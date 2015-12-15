<?php

namespace Terminus\Models;

use Terminus\Models\Organization;

// TODO: this should inherit from TerminusModel, with an `organization` property
class OrganizationSiteMembership extends Organization {

  /**
   * Removes a site from this organization
   *
   * @return Workflow
   */
  public function removeMember() {
    $site     = $this->get('site');
    $workflow = $this->organization->workflows->create(
      'remove_organization_site_membership',
      array(
        'params'    => array(
          'site_id' => $site->id
        )
      )
    );
    return $workflow;
  }

}
