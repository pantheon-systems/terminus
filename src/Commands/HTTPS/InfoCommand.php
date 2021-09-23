<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand.
 *
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;

    /**
     * Provides information for HTTPS on being used for the environment.
     *
     * @authorize
     *
     * @command https:info
     *
     * @field-labels
     *     id: Domain/ID
     *     type: Type
     *     status: Status
     *     status_message: Status Message
     *     deletable: Is Deletable
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     *
     * @usage <site>.<env> Displays HTTPS configuration for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function info($site_env)
    {
        $env = $this->getEnv($site_env);

        return $this->getRowsOfFields($env->getDomains()->fetchWithRecommendations());
    }
}
