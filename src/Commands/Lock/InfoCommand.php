<?php

namespace Pantheon\Terminus\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieves information about an environment lock
     *
     * @authorized
     *
     * @command lock:info
     * @aliases lock
     *
     * @field-labels
     *   locked: Locked?
     *   username: Username
     *   password: Password
     *
     * @param string $site_env The site/environment to retrieve lock information about
     *
     * @return AssociativeList
     */
    public function info($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return new AssociativeList($env->getLock()->serialize());
    }
}
