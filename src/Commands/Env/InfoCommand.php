<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Shows environment information for a site
     *
     * @authorized
     *
     * @command env:info
     *
     * @field-labels
     *   id: ID
     *   created: Created
     *   domain: Domain
     *   locked: Locked
     *   initialized: Initialized
     *   connection_mode: Connection Mode
     *   php_version: PHP Version
     *   drush_version: Drush Version
     *
     * @param string $site_env The site and environment to find the info for.
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     */
    public function getInfo($site_env)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        return new PropertyList($env->serialize());
    }
}
