<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Creates a multidev environment by duplicating another environment
     *
     * @authorized
     *
     * @command multidev:create
     * @aliases env:create
     *
     * @param string $site_env Site & environment to copy from, in the form `site-name.env`.
     * @param string $multidev Name of the new multidev environment being created
     *
     * @usage terminus multidev:create awesome-site.dev new-environment
     *   Creates a new multidev environment named new-environment from the dev environment of awesome-site
     */
    public function createMultidev($site_env, $multidev)
    {
        list($site, $env) = $this->getSiteEnv($site_env, 'dev');
        $workflow = $site->getEnvironments()->create($multidev, $env);
        $workflow->wait();
        $message = $workflow->getMessage();
        if ($workflow->isSuccessful()) {
            $this->log()->notice($message);
        } else {
            throw new TerminusException($message);
        }
    }
}
