<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disables HTTPS and removes the SSL certificate from the environment.
     *
     * @authorize
     *
     * @command https:remove
     * @aliases https:disable https:rm
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Disables HTTPS and removes the SSL certificate from <site>'s <env> environment.
     */
    public function remove($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        // Push the settings change
        $env->disableHttpsCertificate();
        // Converge the environment bindings to get the settings to take effect.
        $workflow = $env->convergeBindings();
        $this->log()->notice("HTTPS has been disabled and the environment's bindings will now be converged.");

        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
