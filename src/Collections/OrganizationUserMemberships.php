<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\OrganizationUserMembership;

/**
 * Class OrganizationUserMemberships
 * @package Pantheon\Terminus\Collections
 */
class OrganizationUserMemberships extends TerminusCollection
{
    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var string
     */
    protected $collected_class = OrganizationUserMembership::class;
    /**
     * @var boolean
     */
    protected $paged = true;

    /**
     * Instantiates the collection, sets param members as properties
     *
     * @param array $options To be set to $this->key
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        $this->organization = $options['organization'];
        $this->url = "organizations/{$this->organization->id}/memberships/users";
    }

    /**
     * Adds a user to this organization
     *
     * @param string $uuid UUID of user user to add to this organization
     * @param string $role Role to assign to the new member
     * @return Workflow $workflow
     */
    public function create($uuid, $role)
    {
        $workflow = $this->organization->getWorkflows()->create(
            'add_organization_user_membership',
            ['params' => ['user_email' => $uuid, 'role' => $role,]]
        );
        return $workflow;
    }

    /**
     * Retrieves models by either user ID, email address, or full name
     *
     * @param string $id Either a user ID, email address, or full name
     * @return OrganizationUserMembership
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $models = $this->getMembers();
        if (isset($models[$id])) {
            return $models[$id];
        }
        foreach ($models as $model) {
            $user = $model->getUser();
            if (in_array($id, [$user->id, $user->get('email'), $user->getProfile()->full_name])) {
                return $model;
            }
        }
        throw new TerminusNotFoundException(
            'An organization member identified by "{id}" could not be found.',
            compact('id')
        );
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
