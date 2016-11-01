<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class WipeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Completely wipe and reset a site
     *
     * @authorized
     *
     * @command env:wipe
     *
     * @param string $site_env The id of the site environment to wipe.
     *
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function wipeEnv($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->wipe();
        $this->log()->notice(
            'Wiping the "{env}" environment of "{site_id}"',
            ['site_id' => $site->get('name'), 'env' => $env->get('id')]
        );
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
