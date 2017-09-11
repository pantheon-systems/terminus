<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

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
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @usage <site>.<env> Displays HTTPS configuration for <site>'s <env> environment.
     */
    public function info($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);
        return new RowsOfFields($env->getDomains()->fetchWithRecommendations()->serialize());
    }
}
