<?php

namespace Pantheon\Terminus\Models;

use Terminus\Models\Site;

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
        // @TODO: Follow this dependency chain and invert it.
        $this->site = new Site($attributes->site);
        $this->site->memberships = [$this,];
        $this->user = $options['collection']->getUser();
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return "{$this->user->id}: Team";
    }
}
