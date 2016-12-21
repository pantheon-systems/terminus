<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class CreateCommand
 * @package Pantheon\Terminus\Commands\Multidev
 */
class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Create a multidev environment by duplicating another environment
     *
     * @authorize
     *
     * @command multidev:create
     * @aliases env:create
     *
     * @param string $site_env Site & environment to copy from, in the form `site-name.env`
     * @param string $multidev Name of the new multidev environment being created
     *
     * @usage terminus <site>.<env> <multidev>
     *   Creates a new multidev environment named <multidev> from the <env> environment of <site>
     */
    public function create($site_env, $multidev)
    {
        list($site, $env) = $this->getSiteEnv($site_env, 'dev');
        $workflow = $site->getEnvironments()->create($multidev, $env);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
