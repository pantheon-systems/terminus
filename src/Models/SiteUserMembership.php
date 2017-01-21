<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Class SiteUserMembership
 * @package Pantheon\Terminus\Models
 */
class SiteUserMembership extends TerminusModel implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Site
     */
    public $site;
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
     * @return SiteUserMembership
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->site = $options['collection']->site;
        $this->user_data = $attributes->user;
    }

    /**
     * Remove membership, either org or user
     *
     * @return Workflow
     **/
    public function delete()
    {
        return $this->getSite()->getWorkflows()->create(
            'remove_site_user_membership',
            ['params' => ['user_id' =>  $this->getUser()->id,],]
        );
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
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

    public function serialize()
    {
        $user = $this->getUser()->serialize();
        return $user + [
            'role'  => $this->get('role'),
        ];
    }

    /**
     * Changes the role of the given member
     *
     * @param string $role Desired role for this member
     * @return Workflow
     */
    public function setRole($role)
    {
        return $this->getSite()->getWorkflows()->create(
            'update_site_user_membership',
            ['params' => ['user_id' =>  $this->getUser()->id, 'role' => $role,],]
        );
    }
}
