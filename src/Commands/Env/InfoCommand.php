<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Show information about an environment
     *
     * @authorize
     *
     * @command env:info
     * @aliases env
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
     * @return PropertyList
     *
     * @param string $site_env The site and environment to retrieve information about
     *
     * @usage env:info <site>.<env>
     *    Gives information about the <env> environment of <site>
     */
    public function info($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return new PropertyList($env->serialize());
    }
}
