<?php

namespace Pantheon\Terminus\Request;

/**
 * Class RequestAwareTrait
 * @package Pantheon\Terminus\Request
 */
trait RequestAwareTrait
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * Inject a pre-configured request object.
     *
     * @param Request $request
     * @return mixed
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Return the request object.
     *
     * @return \Terminus\Request
     */
    public function request()
    {
        return $this->request;
    }
}
