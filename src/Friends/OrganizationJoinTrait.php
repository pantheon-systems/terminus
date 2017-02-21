<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Organization;

/**
 * Class OrganizationJoinTrait
 * @package Pantheon\Terminus\Friends
 */
trait OrganizationJoinTrait
{
    /**
     * @var Organization
     */
    private $organization;

    /**
     * @inheritdoc
     */
    public function getReferences()
    {
        return array_merge(parent::getReferences(), $this->getOrganization()->getReferences());
    }

    /**
     * @inheritdoc
     */
    public function getOrganization()
    {
        if (empty($this->organization)) {
            $organization = $this->getContainer()->get(Organization::class, [$this->get('organization'),]);
            $organization->memberships = [$this,];
            $this->setOrganization($organization);
        }
        return $this->organization;
    }

    /**
     * @inheritdoc
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
    }
}
