<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

class OrganizationUserMembership extends TerminusModel implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var User
     */
    public $user;

    /**
     * @var \stdClass
     */
    protected $user_data;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->user_data = $attributes->user;
        $this->organization = $options['collection']->organization;
    }

    /**
     * Removes a user from this organization
     *
     * @return Workflow
     */
    public function delete()
    {
        $workflow = $this->organization->workflows->create(
            'remove_organization_user_membership',
            ['params' => ['user_id' => $this->getUser()->id,],]
        );
        return $workflow;
    }

    /**
     * Sets the user's role within this organization
     *
     * @param string $role Role for this user to take in the organization
     * @return Workflow
     */
    public function setRole($role)
    {
        $workflow = $this->organization->workflows->create(
            'update_organization_user_membership',
            ['params' => ['user_id' => $this->getUser()->id, 'role' => $role,],]
        );
        return $workflow;
    }

    /**
     * Get the user for this membership
     *
     * @return User
     */
    public function getUser()
    {
        if (empty($this->user)) {
            $this->user = $this->getContainer()->get(User::class, [$this->user_data]);
            $this->user->memberships = [$this,];
        }
        return $this->user;
    }
}
