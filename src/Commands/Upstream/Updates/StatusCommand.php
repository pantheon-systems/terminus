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
     * @return string
     *
     * @usage <site>.<env> Displays the status of updates available from the upstream for <site>'s <env> environment.
     */
    public function status($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return $env->getUpstreamStatus()->getStatus();
    }
}
