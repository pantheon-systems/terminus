<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class StatusCommand extends UpdatesCommand
{
    /**
     * Displays a whether there are updates available from the upstream for a site's environment.
     *
     * @authorize
     *
     * @command upstream:updates:status
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @return string Either 'outdated' or 'current'
     *
     * @usage <site>.<env> Displays either "outdated" if <site>'s <env> environment has upstream updates or "current" if not.
     */
    public function status($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return $env->getUpstreamStatus()->getStatus();
    }
}
