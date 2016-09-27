<?php

namespace Terminus\Models;

class UserSiteMembership extends TerminusModel
{
    /**
     * @var Site
     */
    public $site;
    /**
     * @var User
     */
    public $user;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->site = new Site($attributes->site);
        $this->site->memberships = [$this,];
        $this->user = $options['collection']->user;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->user->id}: Team";
    }
}
