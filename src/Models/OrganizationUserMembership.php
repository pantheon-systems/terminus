<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Class OrganizationUserMembership
 * @package Pantheon\Terminus\Models
 */
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
     * @var object
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
        $this->organization = $options['collection']->getOrganization();
    }

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
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
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

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        $user = $this->getUser();
        $profile = $user->get('profile');
        return [
            'id' => $user->id,
            'first_name' => $profile->firstname,
            'last_name' => $profile->lastname,
            'email' => $user->get('email'),
            'role' => $this->get('role'),
        ];
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
