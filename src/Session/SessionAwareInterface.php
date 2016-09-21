<?php

namespace Pantheon\Terminus\Session;


/**
 * Interface SessionAwareInterface
 * @package Pantheon\Terminus\Session
 *
 * Provides an interface for direct injection of the session helper.
 */
interface SessionAwareInterface
{

    /***
     * @param Session $session
     * @return void
     */
    public function setSession(Session $session);

    /**
     * @return Session
     */
    public function session();

}
