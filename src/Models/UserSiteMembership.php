<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;

/**
 * Class UserSiteMembership
 * @package Pantheon\Terminus\Models
 */
class UserSiteMembership extends TerminusModel implements ContainerAwareInterface
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
     * @var \stdClass
     */
    protected $site_info;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->site_info = $attributes->site;
        $this->user = $options['collection']->getUser();
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->user->id}: Team";
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        if (!$this->site) {
            $this->site = $this->getContainer()->get(Site::class, [$this->site_info]);
            $this->site->memberships = [$this,];
        }
        return $this->site;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
