<?php

namespace Pantheon\Terminus\VcsApi;

use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class VcsClientAwareTrait.
 *
 * @package \Pantheon\Terminus\VcsApi
 */
trait VcsClientAwareTrait
{
    use RequestAwareTrait;

    /**
     * @var \Pantheon\Terminus\VcsApi\Client
     */
    protected Client $vcsClient;

    /**
     * Return the Vcs client object.
     *
     * @return \Pantheon\Terminus\VcsApi\Client
     */
    public function getVcsClient(): Client
    {
        if (isset($this->vcsClient)) {
            return $this->vcsClient;
        }

        $polling_interval = $this->getConfig()->get('http_retry_delay_ms', 1000);

        return $this->vcsClient = new Client($this->request(), $polling_interval);
    }
}
