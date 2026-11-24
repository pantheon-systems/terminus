<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Request;

/**
 * Class RequestAwareTrait
 * @package Pantheon\Terminus\Request
 */
trait RequestAwareTrait
{
    /**
     * @var \Pantheon\Terminus\Request\Request
     */
    protected Request $request;

    /**
     * Inject a pre-configured request object.
     *
     * @param \Pantheon\Terminus\Request\Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Return the request object.
     *
     * @return \Pantheon\Terminus\Request\Request
     */
    public function request(): Request
    {
        return $this->request;
    }
}
