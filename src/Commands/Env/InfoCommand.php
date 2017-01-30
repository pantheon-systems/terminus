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
     * Displays environment status and configuration.
     *
     * @authorize
     *
     * @command env:info
     *
     * @field-labels
     *     id: ID
     *     created: Created
     *     domain: Domain
     *     locked: Locked
     *     initialized: Initialized
     *     connection_mode: Connection Mode
     *     php_version: PHP Version
     *     drush_version: Drush Version
     * @return PropertyList
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage env:info <site>.<env>
     *    Displays status and configuration for <site>'s <env> environment.
     */
    public function info($site_env)
    {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env);
        return new PropertyList($env->serialize());
    }
}
