<?php

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
    protected $request;

    /**
     * Inject a pre-configured request object.
     *
     * @param \Pantheon\Terminus\Request\Request $request
     * @return mixed
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Return the request object.
     *
     * @return \Pantheon\Terminus\Request\Request
     */
    public function request()
    {
        return $this->request;
    }
}
