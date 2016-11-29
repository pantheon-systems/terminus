<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Class UserOrganizationMembership
 * @package Pantheon\Terminus\Models
 */
class UserOrganizationMembership extends TerminusModel implements ContainerAwareInterface
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
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->user = $options['collection']->getUser();
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        if (empty($this->organization)) {
            $this->organization = $this->getContainer()->get(Organization::class, [$this->get('organization')]);
            $this->organization->memberships = [$this,];
        }
        return $this->organization;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
