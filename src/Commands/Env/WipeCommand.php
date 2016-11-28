<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class WipeCommand
 * Testing class for Pantheon\Terminus\Commands\Env\WipeCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class WipeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Completely wipe and reset an environment
     *
     * @authorize
     *
     * @command env:wipe
     *
     * @param string $site_env The name or UUID of the site/environment to wipe
     *
     * @throws TerminusException
     */
    public function wipe($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->wipe();
        $this->log()->notice(
            'Wiping the "{env}" environment of "{site}"',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
