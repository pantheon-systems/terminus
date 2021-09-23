<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

/**
 * Class StatusCommand.
 *
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class StatusCommand extends UpdatesCommand
{
    /**
     * Displays a whether there are updates available from the upstream for a site environment.
     *
     * @authorize
     *
     * @command upstream:updates:status
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @return string Either 'outdated' or 'current'
     * @usage <site>.<env> Displays either "outdated" if <site>'s <env> environment has upstream updates or "current" if not.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function status($site_env)
    {
        return $this->getEnv($site_env)->getUpstreamStatus()->getStatus();
    }
}
