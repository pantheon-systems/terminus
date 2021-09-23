<?php

namespace Pantheon\Terminus\Commands\Lock;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand.
 *
 * @package Pantheon\Terminus\Commands\Lock
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Displays HTTP basic authentication status and configuration for the environment.
     *
     * @authorize
     *
     * @command lock:info
     *
     * @field-labels
     *     locked: Locked?
     *     username: Username
     *     password: Password
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays HTTP basic authentication status and configuration for <site>'s <env> environment.
     *
     * @return \Consolidation\OutputFormatters\StructuredData\PropertyList
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function info($site_env)
    {
        return $this->getPropertyList($this->getEnv($site_env)->getLock());
    }
}
