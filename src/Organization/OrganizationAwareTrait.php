<?php

namespace Pantheon\Terminus\Organization;

use Pantheon\Terminus\Collections\Organizations;
use Pantheon\Terminus\Models\Organization;

trait OrganizationAwareTrait
{

    /**
     * @var Organizations
     */
    protected $organizations;

    /**
     * @param \Pantheon\Terminus\Organization\Organizations $organizations
     *
     * @return mixed
     */
    public function setOrganizations(Organizations $organizations)
    {
        $this->organizations = $organizations;
    }

    /**
     * @return \Pantheon\Terminus\Organization\Organizations
     */
    public function organizations(): Organizations
    {
        if (empty($this->organizations)) {
            $this->setOrganizations(new Organizations());
        }
        return $this->organizations;
    }

    /**
     * @param string $organization_id
     *
     * @return \Pantheon\Terminus\Organization\Organization
     */
    public function getOrganization(string $organization_id): ?Organization
    {
        return $this->organizations()->get($organization_id);
    }
}
