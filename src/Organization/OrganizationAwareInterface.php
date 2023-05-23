<?php

namespace Pantheon\Terminus\Organization;

use Pantheon\Terminus\Collections\Organizations;
use Pantheon\Terminus\Models\Organization;

/**
 * Interface OrganizationAwareInterface
 */
interface OrganizationAwareInterface
{

    /**
     * @param \Pantheon\Terminus\Organization\Organizations $organizations
     *
     * @return mixed
     */
    public function setOrganizations(Organizations $organizations);

    /**
     * @return \Pantheon\Terminus\Organization\Organizations
     */
    public function organizations(): Organizations;

    /**
     * @param string $organization_id
     *
     * @return \Pantheon\Terminus\Organization\Organization
     */
    public function getOrganization(string $organization_id): ?Organization;
}
