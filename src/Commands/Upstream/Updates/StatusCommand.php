<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class StatusCommand
 * @package Pantheon\Terminus\Commands\Upstream\Updates
 */
class StatusCommand extends UpdatesCommand
{

    /**
     * Gives the upstream status for a site's environment.
     *
     * @authorize
     *
     * @command upstream:updates:status
     *
     * @param string $site_env Site & environment
     *
     * @return PropertyList
     *
     * @usage <site>.<env> Gives the upstream updates status for <site>'s <env> environment.
     */
    public function status($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');
        $status = (count($env->getCommits()->getReadyToCopy()) > 0) ? 'outdated' : 'current';
        return new PropertyList(compact('status'));
    }
}
