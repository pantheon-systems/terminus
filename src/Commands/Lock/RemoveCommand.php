<?php

namespace Pantheon\Terminus\Commands\Lock;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Removes an environment lock
     *
     * @authorized
     *
     * @command lock:remove
     * @aliases lock:rm
     *
     * @param string $site_env The site/environment to unlock
     */
    public function remove($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->getLock()->remove();
        while (!$workflow->checkProgress()) {
            // @TODO: Remove Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            '{site}.{env} has been unlocked.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
