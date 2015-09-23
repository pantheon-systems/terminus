<?php

namespace Terminus\Models;

use Terminus\Models\TerminusModel;

class OrganizationSiteMembership extends Organization {

  /**
   * Adds a site to this organization
   *
   * @return [Workflow] $workflow
   */
  public function removeMember() {
    $site     = $this->get('site');
    $workflow = $this->workflows->create(
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
