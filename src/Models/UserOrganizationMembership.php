<?php

namespace Pantheon\Terminus\Models;

use Terminus\Models\Organization;

class UserOrganizationMembership extends TerminusModel
{
    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var User
     */
    public $user;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->user = $options['collection']->getUser();
        // @TODO: Follow this dependency chain and invert it.
        $this->organization = new Organization($attributes->organization);
        $this->organization->memberships = [$this,];
    }
}
