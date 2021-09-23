<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

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
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     *
     * @usage <site>.<env> Displays status and configuration for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function info($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);

        return $this->getPropertyList($this->getEnv($site_env));
    }
}
