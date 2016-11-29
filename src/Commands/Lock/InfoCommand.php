<?php

namespace Pantheon\Terminus\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Lock
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieve information about an environment lock
     *
     * @authorize
     *
     * @command lock:info
     * @aliases lock
     *
     * @field-labels
     *   locked: Locked?
     *   username: Username
     *   password: Password
     * @return PropertyList
     *
     * @param string $site_env The site/environment to retrieve lock information about
     *
     * @usage terminus lock:info <site>.<env>
     *    Displays information about the lock status of the <env> environment of <site>
     */
    public function info($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return new PropertyList($env->getLock()->serialize());
    }
}
