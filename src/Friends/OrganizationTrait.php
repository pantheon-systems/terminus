<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Organization;

/**
 * Class OrganizationTrait
 * @package Pantheon\Terminus\Friends
 */
trait OrganizationTrait
{
    /**
     * @var Organization
     */
    private $organization;

    /**
     * @return Organization Returns a Organization-type object
     */
    public function getOrganization()
    {
        if (empty($this->organization) && isset($this->collection)) {
            $this->setOrganization($this->collection->getOrganization());
        }
        return $this->organization;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
    }
}
