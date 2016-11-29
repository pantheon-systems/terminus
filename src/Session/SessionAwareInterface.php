<?php

namespace Pantheon\Terminus\Session;

use Pantheon\Terminus\Models\User;

/**
 * Interface SessionAwareInterface
 * Provides an interface for direct injection of the session helper.
 * @package Pantheon\Terminus\Session
 */
interface SessionAwareInterface
{

    /***
     * Inject a session object.
     *
     * @param Session $session
     * @return void
     */
    public function setSession(Session $session);

    /**
     * Get the current user's session object.
     *
     * @return Session
     */
    public function session();

    /**
     * Get the user model of the logged in user.
     *
     * @return User
     */
    public function getUser();
}
