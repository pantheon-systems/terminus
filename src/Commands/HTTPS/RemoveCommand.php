<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand.
 *
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Disables HTTPS and removes the SSL certificate from the environment.
     *
     * @authorize
     *
     * @command https:remove
     * @aliases https:disable https:rm
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @usage <site>.<env> Disables HTTPS and removes the SSL certificate from <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function remove($site_env)
    {
        // Launch a workflow to remove the cert, bindings will be converged as part of this
        $workflow = $this->getEnv($site_env)->disableHttpsCertificate();

        // Wait for the workflow to complete.
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
