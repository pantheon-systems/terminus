<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\OrganizationInterface;
use Pantheon\Terminus\Friends\OrganizationTrait;
use Pantheon\Terminus\Friends\UserJoinInterface;
use Pantheon\Terminus\Friends\UserJoinTrait;

/**
 * Class OrganizationUserMembership
 * @package Pantheon\Terminus\Models
 */
class OrganizationUserMembership extends TerminusModel implements ContainerAwareInterface, OrganizationInterface, UserJoinInterface
{
    use ContainerAwareTrait;
    use OrganizationTrait;
    use UserJoinTrait;

    const PRETTY_NAME = 'organization-user membership';

    /**
     * Removes a user from this organization
     *
     * @return Workflow
     */
    public function delete()
    {
        return $this->getOrganization()->getWorkflows()->create(
            'remove_organization_user_membership',
            ['params' => ['user_id' => $this->getUser()->id,],]
        );
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return array_merge($this->getUser()->serialize(), ['role' => $this->get('role'),]);
    }

    /**
     * Sets the user's role within this organization
     *
     * @param string $role Role for this user to take in the organization
     * @return Workflow
     */
    public function setRole($role)
    {
        return $this->getOrganization()->getWorkflows()->create(
            'update_organization_user_membership',
            ['params' => ['user_id' => $this->getUser()->id, 'role' => $role,],]
        );
    }
}
