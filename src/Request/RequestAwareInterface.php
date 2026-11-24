<?php

declare(strict_types=1);

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
     */
    public function setRequest(Request $request): void;

    /**
     * Return the request object.
     *
     * @return Request
     */
    public function request(): Request;
}
