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
     * Creates a multidev environment.
     *
     * @authorize
     *
     * @command multidev:create
     * @aliases env:create
     *
     * @param string $site_env Site & source environment in the format `site-name.env`
     * @param string $multidev Multidev environment name
     *
     * @usage terminus multidev:create <site>.<env> <multidev> Creates the Multidev environment, <multidev>, within <site> with database and files from the <env> environment. If there is an existing Git branch named <multidev>, then it will be used when the new environment is created.
     */
    public function create($site_env, $multidev)
    {
        list($site, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');
        $workflow = $site->getEnvironments()->create($multidev, $env);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
