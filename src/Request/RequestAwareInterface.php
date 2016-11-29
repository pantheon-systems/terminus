<?php

namespace Pantheon\Terminus\Request;

/**
 * Interface RequestAwareInterface
 * @package Pantheon\Terminus\Request
 */
interface RequestAwareInterface
{
    /**
     * Inject a pre-configured request object.
     *
     * @param Request $request
     * @return mixed
     */
    public function setRequest(Request $request);

    /**
     * Return the request object.
     *
     * @return Request
     */
    public function request();
}
