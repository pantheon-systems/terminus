<?php

namespace Pantheon\Terminus\Commands\Https;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DeleteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Delete or disable https for a site.
     *
     * @authorized
     *
     * @command https:delete
     *
     * @param string $site_env Site and environment in the form `site-name.env`.
     *
     * @usage terminus https:delete <site_name>.<env_name>
     */
    public function delete($site_env)
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
